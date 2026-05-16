<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use App\Repository\WorkoutSetRepository;
use App\Service\PersonalRecordCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PersonalRecordCalculatorTest extends TestCase {
    public function testFindPersonalRecordsPicksWeightVolumeAnd1RmCorrectly(): void {
        $user = new User();
        $exercise = new Exercise();

        $sets = [
            // (weight kg, reps) — kept distinct so each kind has a unique winner
            $this->makeSet($exercise, 100.0, 1),  // weight PR (heaviest)
            $this->makeSet($exercise, 80.0, 10),  // 1RM by Epley: 80 * (1 + 10/30) ≈ 106.67
            $this->makeSet($exercise, 60.0, 20),  // volume PR: 60 × 20 = 1200; Epley = 60*(1+20/30)=100
        ];

        $calc = new PersonalRecordCalculator($this->makeRepo($sets));

        $records = $calc->findPersonalRecords($user, $exercise);

        self::assertArrayHasKey(PersonalRecordCalculator::KIND_WEIGHT, $records);
        self::assertArrayHasKey(PersonalRecordCalculator::KIND_VOLUME, $records);
        self::assertArrayHasKey(PersonalRecordCalculator::KIND_1RM, $records);

        self::assertSame(100.0, $records[PersonalRecordCalculator::KIND_WEIGHT]->getWeightKgAsFloat());
        self::assertSame(20, $records[PersonalRecordCalculator::KIND_VOLUME]->getReps());
        self::assertSame(60.0, $records[PersonalRecordCalculator::KIND_VOLUME]->getWeightKgAsFloat());
        // 1RM winner is the 80 kg × 10 set (Epley ≈ 106.67 > 100)
        self::assertSame(80.0, $records[PersonalRecordCalculator::KIND_1RM]->getWeightKgAsFloat());
        self::assertSame(10, $records[PersonalRecordCalculator::KIND_1RM]->getReps());
    }

    public function testTieOnWeightGoesToTheFirstSeenAndMoreRepsWinsFor1Rm(): void {
        $user = new User();
        $exercise = new Exercise();

        // Two sets tied on weight. Weight PR uses strict `>`, so the first set seen wins
        // on ties. 1RM compares Epley scores, so more reps wins.
        $sets = [
            $this->makeSet($exercise, 100.0, 3),
            $this->makeSet($exercise, 100.0, 5),
        ];

        $calc = new PersonalRecordCalculator($this->makeRepo($sets));

        $records = $calc->findPersonalRecords($user, $exercise);

        self::assertSame(3, $records[PersonalRecordCalculator::KIND_WEIGHT]->getReps());
        self::assertSame(5, $records[PersonalRecordCalculator::KIND_1RM]->getReps());
    }

    public function testZeroRepSetsAreSkipped(): void {
        $user = new User();
        $exercise = new Exercise();

        $sets = [
            $this->makeSet($exercise, 200.0, 0),  // should be skipped despite huge weight
            $this->makeSet($exercise, 50.0, 5),
        ];

        $calc = new PersonalRecordCalculator($this->makeRepo($sets));

        $records = $calc->findPersonalRecords($user, $exercise);

        self::assertSame(50.0, $records[PersonalRecordCalculator::KIND_WEIGHT]->getWeightKgAsFloat());
        self::assertSame(5, $records[PersonalRecordCalculator::KIND_WEIGHT]->getReps());
    }

    public function testReturnsEmptyArrayWhenAllSetsAreZeroReps(): void {
        $user = new User();
        $exercise = new Exercise();

        $sets = [
            $this->makeSet($exercise, 100.0, 0),
            $this->makeSet($exercise, 50.0, 0),
        ];

        $calc = new PersonalRecordCalculator($this->makeRepo($sets));

        $records = $calc->findPersonalRecords($user, $exercise);

        self::assertSame([], $records);
    }

    private function makeSet(Exercise $exercise, float $weight, int $reps): WorkoutSet {
        $workout = new Workout();
        $set = new WorkoutSet();
        $set->setWorkout($workout);
        $set->setExercise($exercise);
        $set->setReps($reps);
        $set->setWeightKg(number_format($weight, 2, '.', ''));

        return $set;
    }

    /**
     * @param list<WorkoutSet> $sets
     */
    private function makeRepo(array $sets): WorkoutSetRepository {
        return new class($sets) extends WorkoutSetRepository {
            /**
             * @param list<WorkoutSet> $sets
             */
            public function __construct(private array $sets) {
                // intentionally skip parent constructor — no DB needed for unit tests
            }

            public function findAllForExerciseByUser(User $user, Exercise $exercise): array {
                return $this->sets;
            }
        };
    }
}
