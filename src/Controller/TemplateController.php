<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Workout;
use App\Form\WorkoutType;
use App\Repository\WorkoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function sprintf;

#[IsGranted('ROLE_USER')]
#[Route('/templates', name: 'template_')]
final class TemplateController extends AbstractController {
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(WorkoutRepository $workouts): Response {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('template/index.html.twig', [
            'templates' => $workouts->findUserTemplates($user),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response {
        /** @var User $user */
        $user = $this->getUser();

        $template = new Workout();
        $template->setUser($user);
        $template->setIsTemplate(true);

        $form = $this->createForm(WorkoutType::class, $template, ['as_template' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $template->setIsTemplate(true);
            $template->renumberSets();

            $em->persist($template);
            $em->flush();

            $this->addFlash('success', sprintf('Šablona „%s" byla vytvořena.', $template->getName()));

            return $this->redirectToRoute('template_index');
        }

        return $this->render('template/new.html.twig', [
            'form' => $form,
        ]);
    }
}
