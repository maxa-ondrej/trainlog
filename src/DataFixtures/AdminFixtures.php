<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminFixtures extends Fixture implements FixtureGroupInterface {
    public const REFERENCE = 'demo-admin';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public static function getGroups(): array {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void {
        $admin = new User();
        $admin->setEmail('admin@trainlog.local');
        $admin->setName('Demo admin');
        $admin->setRole(Role::Admin);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));

        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::REFERENCE, $admin);
    }
}
