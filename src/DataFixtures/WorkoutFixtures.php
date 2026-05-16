<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Exercise;
use App\Entity\User;
use App\Entity\Workout;
use App\Entity\WorkoutSet;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function count;
use function sprintf;

final class WorkoutFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface {
    /**
     * 3 workouts per week × 6 weeks = 18 workouts.
     * Rotating A/B push/pull/legs split.
     *
     * @var list<array{name: string, exercises: list<array{name: string, sets: int, reps: int, base: float, step: float}>}>
     */
    private const TEMPLATES = [
        [
            'name' => 'Push',
            'exercises' => [
                ['name' => 'Benchpress', 'sets' => 4, 'reps' => 5, 'base' => 60.0, 'step' => 2.5],
                ['name' => 'Tlak nad hlavu (OHP)', 'sets' => 3, 'reps' => 8, 'base' => 40.0, 'step' => 1.25],
                ['name' => 'Kliky na bradlech', 'sets' => 3, 'reps' => 10, 'base' => 0.0, 'step' => 1.0],
            ],
        ],
        [
            'name' => 'Pull',
            'exercises' => [
                ['name' => 'Mrtvý tah', 'sets' => 3, 'reps' => 5, 'base' => 100.0, 'step' => 5.0],
                ['name' => 'Veslování v předklonu', 'sets' => 4, 'reps' => 8, 'base' => 50.0, 'step' => 2.5],
                ['name' => 'Shyby', 'sets' => 3, 'reps' => 6, 'base' => 0.0, 'step' => 1.0],
            ],
        ],
        [
            'name' => 'Legs',
            'exercises' => [
                ['name' => 'Dřep', 'sets' => 4, 'reps' => 5, 'base' => 80.0, 'step' => 2.5],
                ['name' => 'Výpady', 'sets' => 3, 'reps' => 10, 'base' => 30.0, 'step' => 1.0],
                ['name' => 'Leg curl', 'sets' => 3, 'reps' => 12, 'base' => 25.0, 'step' => 1.0],
                ['name' => 'Leg extension', 'sets' => 3, 'reps' => 12, 'base' => 30.0, 'step' => 1.0],
            ],
        ],
    ];

    public static function getGroups(): array {
        return ['demo'];
    }

    public function getDependencies(): array {
        return [UserFixtures::class, ExerciseFixtures::class];
    }

    public function load(ObjectManager $manager): void {
        /** @var User $user */
        $user = $this->getReference(UserFixtures::DEMO_REFERENCE, User::class);

        $today = new DateTimeImmutable('today');

        for ($week = 5; $week >= 0; --$week) {
            foreach ([0, 2, 4] as $offsetInWeek => $dayOffset) {
                $template = self::TEMPLATES[$offsetInWeek % count(self::TEMPLATES)];
                $performedAt = $today->modify(sprintf('-%d days', $week * 7 + (5 - $dayOffset)));

                $workout = new Workout();
                $workout->setUser($user);
                $workout->setName($template['name']);
                $workout->setPerformedAt($performedAt);
                $workout->setDurationMinutes(55 + ($week % 3) * 5);

                $position = 1;
                foreach ($template['exercises'] as $spec) {
                    /** @var Exercise $exercise */
                    $exercise = $this->getReference(ExerciseFixtures::referenceFor($spec['name']), Exercise::class);

                    // Progressive overload: base weight grows by step × week passed
                    $weeksPassed = 5 - $week;
                    $weight = $spec['base'] + $spec['step'] * $weeksPassed;

                    for ($i = 0; $i < $spec['sets']; ++$i) {
                        $set = new WorkoutSet();
                        $set->setExercise($exercise);
                        $set->setReps($spec['reps']);
                        $set->setWeightKg(number_format($weight, 2, '.', ''));
                        $set->setRpe(number_format(7.5 + ($i / max(1, $spec['sets'] - 1)) * 1.5, 1, '.', ''));
                        $set->setPosition($position++);
                        $workout->addSet($set);
                    }
                }

                $manager->persist($workout);
            }
        }

        $manager->flush();
    }
}
