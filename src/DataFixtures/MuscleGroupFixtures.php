<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\MuscleGroup;
use App\Repository\MuscleGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class MuscleGroupFixtures extends Fixture implements FixtureGroupInterface {
    /** @var list<string> */
    public const NAMES = [
        'prsa',
        'záda',
        'ramena',
        'biceps',
        'triceps',
        'předloktí',
        'kvadricepsy',
        'hamstringy',
        'hýždě',
        'lýtka',
        'břicho',
    ];

    public function __construct(
        private readonly MuscleGroupRepository $muscleGroups,
    ) {}

    public static function getGroups(): array {
        return ['default', 'demo'];
    }

    public function load(ObjectManager $manager): void {
        foreach (self::NAMES as $name) {
            if ($this->muscleGroups->findOneBy(['name' => $name]) !== null) {
                continue;
            }

            $manager->persist(new MuscleGroup($name));
        }

        $manager->flush();
    }
}
