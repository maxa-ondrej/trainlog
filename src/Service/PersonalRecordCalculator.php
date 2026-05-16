<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use App\Repository\WorkoutSetRepository;

final class PersonalRecordCalculator {
    public const KIND_WEIGHT = 'váha';
    public const KIND_VOLUME = 'objem';
    public const KIND_1RM = '1RM';

    public function __construct(
        private readonly WorkoutSetRepository $sets,
    ) {}

    /**
     * For each PR kind, returns the *single* set that holds it for this user+exercise.
     *
     * @return array<string, WorkoutSet>
     */
    public function findPersonalRecords(User $user, Exercise $exercise): array {
        return $this->pickPrsFromHistory($this->sets->findAllForExerciseByUser($user, $exercise));
    }

    /**
     * For a single workout, returns a map setId => list of PR kind labels that
     * this set holds (across the user's whole history). A workout-detail view
     * uses this to badge PR rows.
     *
     * @return array<int, list<string>>
     */
    public function badgeMapForWorkout(Workout $workout): array {
        $user = $workout->getUser();
        if ($user === null) {
            return [];
        }

        $byExercise = $workout->getSetsByExercise();
        if ($byExercise === []) {
            return [];
        }

        $historyByExercise = $this->sets->findAllForExercisesByUserGrouped($user, array_keys($byExercise));

        $out = [];
        foreach ($byExercise as $exId => $entry) {
            foreach ($this->pickPrsFromHistory($historyByExercise[$exId] ?? []) as $kind => $set) {
                $id = $set->getId();
                if ($id !== null && $workout === $set->getWorkout()) {
                    $out[$id] ??= [];
                    $out[$id][] = $kind;
                }
            }
        }

        return $out;
    }

    /**
     * @param iterable<WorkoutSet> $sets
     *
     * @return array<string, WorkoutSet>
     */
    private function pickPrsFromHistory(iterable $sets): array {
        $records = [];
        foreach ($sets as $set) {
            if ($set->getReps() <= 0) {
                continue;
            }
            if (!isset($records[self::KIND_WEIGHT]) || $set->getWeightKgAsFloat() > $records[self::KIND_WEIGHT]->getWeightKgAsFloat()) {
                $records[self::KIND_WEIGHT] = $set;
            }
            if (!isset($records[self::KIND_VOLUME]) || $set->getVolume() > $records[self::KIND_VOLUME]->getVolume()) {
                $records[self::KIND_VOLUME] = $set;
            }
            if (!isset($records[self::KIND_1RM]) || $set->getEstimated1Rm() > $records[self::KIND_1RM]->getEstimated1Rm()) {
                $records[self::KIND_1RM] = $set;
            }
        }

        return $records;
    }
}
