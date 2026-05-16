<?php

declare(strict_types=1);

/*
 * Functional tests for ExerciseController. Boots the kernel in `test` env and relies on
 * fixtures loaded by FixturesWebTestCase (group `demo`). Seeded exercises are owned by
 * the seeded admin (`admin@trainlog.local`) and marked public; `demo@trainlog.local` can
 * see them and `hosta@trainlog.local` (non-owner, non-admin) is denied edit.
 */

namespace App\Tests\Functional;

use App\Entity\Exercise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

use function assert;

/**
 * @internal
 */
final class ExerciseControllerTest extends FixturesWebTestCase {
    public function testIndexRedirectsToLoginWhenAnonymous(): void {
        $client = self::createClient();
        $client->request('GET', '/exercises');

        self::assertResponseStatusCodeSame(302);
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertStringContainsString('/login', $location);
    }

    public function testIndexShowsSeededExercisesForDemoUser(): void {
        $client = self::createClient();
        $client->loginUser($this->getUser($client, 'demo@trainlog.local'));

        $client->request('GET', '/exercises');

        self::assertResponseIsSuccessful();
        // Seeded public exercises from ExerciseFixtures
        self::assertSelectorTextContains('table', 'Benchpress');
        self::assertSelectorTextContains('table', 'Dřep');
    }

    public function testEditingAdminOwnedExerciseAsOtherUserIsDenied(): void {
        $client = self::createClient();
        $client->loginUser($this->getUser($client, 'hosta@trainlog.local'));

        $exercise = $this->findAdminOwnedExercise($client);

        $client->request('GET', '/exercises/'.$exercise->getId().'/edit');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteWithBadCsrfTokenIsRejected(): void {
        $client = self::createClient();
        // Login as admin so we pass the voter and only the CSRF check is left
        $client->loginUser($this->getUser($client, 'admin@trainlog.local'));

        $exercise = $this->findAdminOwnedExercise($client);

        $client->request('POST', '/exercises/'.$exercise->getId().'/delete', [
            '_token' => 'not-a-valid-token',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    private function findAdminOwnedExercise(KernelBrowser $client): Exercise {
        $em = $client->getContainer()->get('doctrine')->getManager();
        assert($em instanceof EntityManagerInterface);
        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin@trainlog.local']);
        assert($admin instanceof User);
        $exercise = $em->getRepository(Exercise::class)->findOneBy(['owner' => $admin]);
        if (!$exercise instanceof Exercise) {
            throw new RuntimeException('No admin-owned exercise found — fixtures missing?');
        }

        return $exercise;
    }
}
