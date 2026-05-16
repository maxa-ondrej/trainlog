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
        $records = [];
        foreach ($this->sets->findAllForExerciseByUser($user, $exercise) as $set) {
            if ($set->getReps() <= 0) {
                continue;
            }
            if (!isset($records[self::KIND_WEIGHT]) || $set->getWeightKgAsFloat() > $records[self::KIND_WEIGHT]->getWeightKgAsFloat()) {
                $records[self::KIND_WEIGHT] = $set;
            }
            if (!isset($records[self::KIND_VOLUME]) || $set->getVolume() > $records[self::KIND_VOLUME]->getVolume()) {
                $records[self::KIND_VOLUME] = $set;
            }
            $epleyA = $set->getWeightKgAsFloat() * (1 + $set->getReps() / 30);
            $epleyB = isset($records[self::KIND_1RM])
                ? $records[self::KIND_1RM]->getWeightKgAsFloat() * (1 + $records[self::KIND_1RM]->getReps() / 30)
                : 0.0;
            if ($epleyA > $epleyB) {
                $records[self::KIND_1RM] = $set;
            }
        }

        return $records;
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

        $byExercise = [];
        foreach ($workout->getSets() as $set) {
            $ex = $set->getExercise();
            if ($ex === null || $ex->getId() === null) {
                continue;
            }
            $byExercise[$ex->getId()] ??= $ex;
        }

        $out = [];
        foreach ($byExercise as $exercise) {
            $prs = $this->findPersonalRecords($user, $exercise);
            foreach ($prs as $kind => $set) {
                $id = $set->getId();
                if ($id === null) {
                    continue;
                }
                if ($workout === $set->getWorkout()) {
                    $out[$id] ??= [];
                    $out[$id][] = $kind;
                }
            }
        }

        return $out;
    }
}
