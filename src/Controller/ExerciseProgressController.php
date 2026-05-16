<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\User;
use App\Repository\WorkoutSetRepository;
use App\Security\Voter\ExerciseVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ExerciseProgressController extends AbstractController {
    public function __construct(
        private readonly WorkoutSetRepository $sets,
    ) {}

    #[Route('/exercises/{id}/progress', name: 'exercise_progress', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Exercise $exercise): Response {
        if (!$this->isGranted(ExerciseVoter::VIEW, $exercise)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('exercise/progress.html.twig', [
            'exercise' => $exercise,
        ]);
    }

    #[Route('/api/exercises/{id}/progress.json', name: 'api_exercise_progress', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function data(Exercise $exercise): JsonResponse {
        if (!$this->isGranted(ExerciseVoter::VIEW, $exercise)) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $rows = $this->sets->findProgressFor($user, $exercise);

        return $this->json([
            'labels' => array_column($rows, 'date'),
            'maxWeight' => array_column($rows, 'maxWeight'),
            'volume' => array_column($rows, 'volume'),
            'estimated1rm' => array_column($rows, 'estimated1rm'),
        ]);
    }
}
