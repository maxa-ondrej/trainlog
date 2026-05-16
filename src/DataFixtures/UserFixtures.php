<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface {
    public const DEMO_REFERENCE = 'demo-user';
    public const SECOND_REFERENCE = 'demo-user-2';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public static function getGroups(): array {
        return ['demo'];
    }

    public function getDependencies(): array {
        return [AdminFixtures::class];
    }

    public function load(ObjectManager $manager): void {
        $demo = new User();
        $demo->setEmail('demo@trainlog.local');
        $demo->setName('Demo uživatel');
        $demo->setRole(Role::User);
        $demo->setPassword($this->hasher->hashPassword($demo, 'demo'));
        $manager->persist($demo);

        $other = new User();
        $other->setEmail('hosta@trainlog.local');
        $other->setName('Druhý uživatel');
        $other->setRole(Role::User);
        $other->setPassword($this->hasher->hashPassword($other, 'demo'));
        $manager->persist($other);

        $manager->flush();

        $this->addReference(self::DEMO_REFERENCE, $demo);
        $this->addReference(self::SECOND_REFERENCE, $other);
    }
}
