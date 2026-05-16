<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Exercise;
use App\Entity\MuscleGroup;
use App\Entity\User;
use App\Repository\MuscleGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ExerciseFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface {
    /** @var list<array{name: string, muscles: list<string>}> */
    public const EXERCISES = [
        ['name' => 'Benchpress', 'muscles' => ['prsa', 'triceps', 'ramena']],
        ['name' => 'Dřep', 'muscles' => ['kvadricepsy', 'hýždě']],
        ['name' => 'Mrtvý tah', 'muscles' => ['záda', 'hamstringy', 'hýždě']],
        ['name' => 'Tlak nad hlavu (OHP)', 'muscles' => ['ramena', 'triceps']],
        ['name' => 'Veslování v předklonu', 'muscles' => ['záda', 'biceps']],
        ['name' => 'Shyby', 'muscles' => ['záda', 'biceps']],
        ['name' => 'Kliky na bradlech', 'muscles' => ['prsa', 'triceps']],
        ['name' => 'Výpady', 'muscles' => ['kvadricepsy', 'hýždě']],
        ['name' => 'Leg curl', 'muscles' => ['hamstringy']],
        ['name' => 'Leg extension', 'muscles' => ['kvadricepsy']],
    ];

    public function __construct(
        private readonly MuscleGroupRepository $muscleGroups,
    ) {}

    public static function getGroups(): array {
        return ['demo'];
    }

    public function getDependencies(): array {
        return [AdminFixtures::class, MuscleGroupFixtures::class];
    }

    public function load(ObjectManager $manager): void {
        /** @var User $admin */
        $admin = $this->getReference(AdminFixtures::REFERENCE, User::class);

        foreach (self::EXERCISES as $spec) {
            $exercise = new Exercise();
            $exercise->setName($spec['name']);
            $exercise->setOwner($admin);
            $exercise->setIsPublic(true);
            foreach ($spec['muscles'] as $muscleName) {
                $mg = $this->muscleGroups->findOneBy(['name' => $muscleName]);
                if ($mg instanceof MuscleGroup) {
                    $exercise->addMuscleGroup($mg);
                }
            }
            $manager->persist($exercise);
            $this->addReference(self::referenceFor($spec['name']), $exercise);
        }

        $manager->flush();
    }

    public static function referenceFor(string $name): string {
        return 'demo-exercise-'.$name;
    }
}
