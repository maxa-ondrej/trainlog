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

        $sets = $this->sets->findAllForExerciseByUser($user, $exercise);

        /** @var array<string, array{maxWeight: float, volume: float, est1rm: float}> $byDate */
        $byDate = [];
        foreach ($sets as $set) {
            $workout = $set->getWorkout();
            if ($workout === null) {
                continue;
            }
            $date = $workout->getPerformedAt()->format('Y-m-d');
            $weight = $set->getWeightKgAsFloat();
            $reps = $set->getReps();
            $epley = $weight * (1 + $reps / 30);

            if (!isset($byDate[$date])) {
                $byDate[$date] = ['maxWeight' => 0, 'volume' => 0, 'est1rm' => 0];
            }
            if ($weight > $byDate[$date]['maxWeight']) {
                $byDate[$date]['maxWeight'] = $weight;
            }
            $byDate[$date]['volume'] += $weight * $reps;
            if ($epley > $byDate[$date]['est1rm']) {
                $byDate[$date]['est1rm'] = round($epley, 2);
            }
        }

        ksort($byDate);
        $labels = array_keys($byDate);
        $maxWeight = array_map(static fn ($r) => $r['maxWeight'], array_values($byDate));
        $volume = array_map(static fn ($r) => $r['volume'], array_values($byDate));
        $estimated1rm = array_map(static fn ($r) => $r['est1rm'], array_values($byDate));

        return $this->json([
            'labels' => $labels,
            'maxWeight' => $maxWeight,
            'volume' => $volume,
            'estimated1rm' => $estimated1rm,
        ]);
    }
}
