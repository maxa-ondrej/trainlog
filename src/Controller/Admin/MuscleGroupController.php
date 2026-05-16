<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MuscleGroup;
use App\Form\MuscleGroupType;
use App\Repository\MuscleGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function sprintf;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/muscle-groups', name: 'admin_muscle_group_')]
final class MuscleGroupController extends AbstractController {
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(MuscleGroupRepository $muscleGroups): Response {
        return $this->render('admin/muscle_group/index.html.twig', [
            'muscleGroups' => $muscleGroups->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response {
        $muscleGroup = new MuscleGroup();
        $form = $this->createForm(MuscleGroupType::class, $muscleGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($muscleGroup);
            $em->flush();

            $this->addFlash('success', sprintf('Svalová partie „%s" byla vytvořena.', $muscleGroup->getName()));

            return $this->redirectToRoute('admin_muscle_group_index');
        }

        return $this->render('admin/muscle_group/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(MuscleGroup $muscleGroup, Request $request, EntityManagerInterface $em): Response {
        $form = $this->createForm(MuscleGroupType::class, $muscleGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('Svalová partie „%s" byla uložena.', $muscleGroup->getName()));

            return $this->redirectToRoute('admin_muscle_group_index');
        }

        return $this->render('admin/muscle_group/edit.html.twig', [
            'form' => $form,
            'muscleGroup' => $muscleGroup,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(MuscleGroup $muscleGroup, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_muscle_group_delete_'.$muscleGroup->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($muscleGroup->getExercises()->count() > 0) {
            $this->addFlash('error', sprintf('Svalovou partii „%s" nelze smazat, je přiřazena k cvikům.', $muscleGroup->getName()));

            return $this->redirectToRoute('admin_muscle_group_index');
        }

        $name = $muscleGroup->getName();
        $em->remove($muscleGroup);
        $em->flush();

        $this->addFlash('success', sprintf('Svalová partie „%s" byla smazána.', $name));

        return $this->redirectToRoute('admin_muscle_group_index');
    }
}
