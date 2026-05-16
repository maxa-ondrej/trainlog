<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Workout;
use App\Form\WorkoutType;
use App\Repository\ExerciseRepository;
use App\Repository\WorkoutRepository;
use App\Security\Voter\WorkoutVoter;
use App\Service\PersonalRecordCalculator;
use App\Service\WorkoutTemplateInstantiator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_array;
use function is_string;
use function sprintf;

#[IsGranted('ROLE_USER')]
#[Route('/workouts', name: 'workout_')]
final class WorkoutController extends AbstractController {
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, WorkoutRepository $workouts, ExerciseRepository $exercises): Response {
        /** @var User $user */
        $user = $this->getUser();

        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));
        $exerciseId = $request->query->getInt('exercise');
        $exercise = $exerciseId > 0 ? $exercises->find($exerciseId) : null;

        $list = $workouts->createUserHistoryQuery($user, $from, $to, $exercise)
            ->getQuery()
            ->getResult();

        return $this->render('workout/index.html.twig', [
            'workouts' => is_array($list) ? $list : [],
            'from' => $from,
            'to' => $to,
            'exercise' => $exercise,
            'exercises' => $exercises->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response {
        /** @var User $user */
        $user = $this->getUser();

        $workout = new Workout();
        $workout->setUser($user);

        return $this->handleForm($workout, $request, $em, isNew: true);
    }

    #[Route('/from-template/{id}', name: 'from_template', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function fromTemplate(Workout $template, Request $request, WorkoutTemplateInstantiator $instantiator): RedirectResponse {
        if (!$this->isGranted(WorkoutVoter::VIEW, $template)) {
            throw $this->createAccessDeniedException();
        }

        if (!$template->isTemplate()) {
            throw $this->createNotFoundException('Not a template.');
        }

        if (!$this->isCsrfTokenValid('workout_from_template_'.$template->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $workout = $instantiator->instantiate($template, $user);

        $this->addFlash('success', sprintf('Trénink ze šablony „%s" byl vytvořen — uprav sady a ulož.', $template->getName()));

        return $this->redirectToRoute('workout_edit', ['id' => $workout->getId()]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Workout $workout, PersonalRecordCalculator $prCalc): Response {
        if (!$this->isGranted(WorkoutVoter::VIEW, $workout)) {
            throw $this->createAccessDeniedException();
        }

        $byExercise = [];
        foreach ($workout->getSets() as $set) {
            $ex = $set->getExercise();
            if ($ex === null) {
                continue;
            }
            $exId = $ex->getId();
            if ($exId === null) {
                continue;
            }
            if (!isset($byExercise[$exId])) {
                $byExercise[$exId] = ['exercise' => $ex, 'sets' => []];
            }
            $byExercise[$exId]['sets'][] = $set;
        }

        return $this->render('workout/show.html.twig', [
            'workout' => $workout,
            'byExercise' => $byExercise,
            'prMap' => $prCalc->badgeMapForWorkout($workout),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Workout $workout, Request $request, EntityManagerInterface $em): Response {
        if (!$this->isGranted(WorkoutVoter::EDIT, $workout)) {
            throw $this->createAccessDeniedException();
        }

        return $this->handleForm($workout, $request, $em, isNew: false);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Workout $workout, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isGranted(WorkoutVoter::DELETE, $workout)) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('workout_delete_'.$workout->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $name = $workout->getName();
        $em->remove($workout);
        $em->flush();

        $this->addFlash('success', sprintf('Trénink „%s" byl smazán.', $name));

        return $this->redirectToRoute('workout_index');
    }

    private function handleForm(Workout $workout, Request $request, EntityManagerInterface $em, bool $isNew): Response {
        $form = $this->createForm(WorkoutType::class, $workout, [
            'as_template' => $workout->isTemplate(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isTemplateRaw = $form->get('isTemplate')->getData();
            $workout->setIsTemplate(is_string($isTemplateRaw) && $isTemplateRaw === '1');

            $position = 1;
            foreach ($workout->getSets() as $set) {
                $set->setPosition($position++);
                $set->setWorkout($workout);
            }

            if ($isNew) {
                $em->persist($workout);
            }
            $em->flush();

            $this->addFlash('success', sprintf('Trénink „%s" byl uložen.', $workout->getName()));

            return $this->redirectToRoute('workout_show', ['id' => $workout->getId()]);
        }

        return $this->render($isNew ? 'workout/new.html.twig' : 'workout/edit.html.twig', [
            'form' => $form,
            'workout' => $workout,
        ]);
    }

    private function parseDate(mixed $raw): ?DateTimeImmutable {
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (Exception) {
            return null;
        }
    }
}
