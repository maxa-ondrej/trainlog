<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\User;
use App\Form\ExerciseType;
use App\Repository\ExerciseRepository;
use App\Repository\MuscleGroupRepository;
use App\Repository\WorkoutSetRepository;
use App\Security\Voter\ExerciseVoter;
use App\Service\PersonalRecordCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_scalar;
use function sprintf;

#[IsGranted('ROLE_USER')]
#[Route('/exercises', name: 'exercise_')]
final class ExerciseController extends AbstractController {
    private const PER_PAGE = 20;

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ExerciseRepository $exercises, MuscleGroupRepository $muscleGroups): Response {
        /** @var User $user */
        $user = $this->getUser();

        /** @var array<int, mixed> $rawIds */
        $rawIds = (array) $request->query->all('muscle_group');
        $selectedIds = [];
        foreach ($rawIds as $rawId) {
            if (is_scalar($rawId) && ctype_digit((string) $rawId)) {
                $selectedIds[] = (int) $rawId;
            }
        }

        $page = max(1, $request->query->getInt('page', 1));

        $items = $exercises->findVisiblePage($user, $selectedIds, $page, self::PER_PAGE);
        $totalCount = $exercises->countVisible($user, $selectedIds);

        return $this->render('exercise/index.html.twig', [
            'items' => $items,
            'totalCount' => $totalCount,
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'muscleGroups' => $muscleGroups->findBy([], ['name' => 'ASC']),
            'selectedIds' => $selectedIds,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response {
        /** @var User $user */
        $user = $this->getUser();

        $exercise = new Exercise();
        $exercise->setOwner($user);
        $form = $this->createForm(ExerciseType::class, $exercise, [
            'admin_mode' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($exercise);
            $em->flush();

            $this->addFlash('success', sprintf('Cvik „%s" byl vytvořen.', $exercise->getName()));

            return $this->redirectToRoute('exercise_show', ['id' => $exercise->getId()]);
        }

        return $this->render('exercise/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Exercise $exercise, WorkoutSetRepository $sets, PersonalRecordCalculator $prCalc): Response {
        if (!$this->isGranted(ExerciseVoter::VIEW, $exercise)) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $recent = $sets->createQueryBuilder('s')
            ->innerJoin('s.workout', 'w')->addSelect('w')
            ->andWhere('s.exercise = :exercise')
            ->andWhere('w.user = :user')
            ->andWhere('w.isTemplate = false')
            ->setParameter('exercise', $exercise)
            ->setParameter('user', $user)
            ->orderBy('w.performedAt', 'DESC')
            ->addOrderBy('s.position', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $prs = $prCalc->findPersonalRecords($user, $exercise);
        $prIds = [];
        foreach ($prs as $kind => $set) {
            $id = $set->getId();
            if ($id !== null) {
                $prIds[$id] ??= [];
                $prIds[$id][] = $kind;
            }
        }

        return $this->render('exercise/show.html.twig', [
            'exercise' => $exercise,
            'recentSets' => $recent,
            'prMap' => $prIds,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Exercise $exercise, Request $request, EntityManagerInterface $em): Response {
        if (!$this->isGranted(ExerciseVoter::EDIT, $exercise)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ExerciseType::class, $exercise, [
            'admin_mode' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('Cvik „%s" byl uložen.', $exercise->getName()));

            return $this->redirectToRoute('exercise_show', ['id' => $exercise->getId()]);
        }

        return $this->render('exercise/edit.html.twig', [
            'form' => $form,
            'exercise' => $exercise,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Exercise $exercise, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isGranted(ExerciseVoter::DELETE, $exercise)) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('exercise_delete_'.$exercise->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$exercise->getWorkoutSets()->isEmpty()) {
            $this->addFlash('error', sprintf('Cvik „%s" nelze smazat, je použit v tréninkových sériích.', $exercise->getName()));

            return $this->redirectToRoute('exercise_show', ['id' => $exercise->getId()]);
        }

        $name = $exercise->getName();
        $em->remove($exercise);
        $em->flush();

        $this->addFlash('success', sprintf('Cvik „%s" byl smazán.', $name));

        return $this->redirectToRoute('exercise_index');
    }
}
