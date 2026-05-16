<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Exercise;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function sprintf;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/exercises', name: 'admin_exercise_')]
final class ExerciseController extends AbstractController {
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ExerciseRepository $exercises): Response {
        return $this->render('admin/exercises/index.html.twig', [
            'exercises' => $exercises->findAllWithOwnerAndMuscleGroups(),
        ]);
    }

    #[Route('/{id}/toggle-public', name: 'toggle_public', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function togglePublic(Exercise $exercise, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_exercise_toggle_public_'.$exercise->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $exercise->setIsPublic(!$exercise->isPublic());
        $em->flush();

        $this->addFlash('success', sprintf('Cvik „%s" je nyní %s.', $exercise->getName(), $exercise->isPublic() ? 'veřejný' : 'soukromý'));

        return $this->redirectToRoute('admin_exercise_index');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Exercise $exercise, Request $request, EntityManagerInterface $em): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_exercise_delete_'.$exercise->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$exercise->isPublic()) {
            $this->addFlash('error', 'Lze mazat pouze veřejné cviky.');

            return $this->redirectToRoute('admin_exercise_index');
        }

        if ($exercise->getWorkoutSets()->count() > 0) {
            $this->addFlash('error', sprintf('Cvik „%s" nelze smazat, je použit v tréninkových sériích.', $exercise->getName()));

            return $this->redirectToRoute('admin_exercise_index');
        }

        $name = $exercise->getName();
        $em->remove($exercise);
        $em->flush();

        $this->addFlash('success', sprintf('Cvik „%s" byl smazán.', $name));

        return $this->redirectToRoute('admin_exercise_index');
    }
}
