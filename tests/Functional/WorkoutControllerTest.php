<?php

declare(strict_types=1);

/*
 * Functional tests for WorkoutController. Boots the kernel in `test` env and relies on
 * fixtures loaded by FixturesWebTestCase (group `demo`). Cross-user privacy and the
 * happy-path create-workout flow are covered. The create-workout test inserts a row;
 * the test fixtures are reloaded for the whole process so subsequent runs stay clean.
 */

namespace App\Tests\Functional;

use App\Entity\Workout;
use Doctrine\ORM\EntityManagerInterface;

use function assert;

/**
 * @internal
 */
final class WorkoutControllerTest extends FixturesWebTestCase {
    public function testWorkoutOfOneUserIsForbiddenForAnother(): void {
        $client = self::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        assert($em instanceof EntityManagerInterface);

        $demoUser = $this->getUser($client, 'demo@trainlog.local');
        $workout = $em->getRepository(Workout::class)->findOneBy(['user' => $demoUser, 'isTemplate' => false]);
        assert($workout instanceof Workout);

        $client->loginUser($this->getUser($client, 'hosta@trainlog.local'));
        $client->request('GET', '/workouts/'.$workout->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreatingWorkoutPersistsItAndRedirects(): void {
        $client = self::createClient();
        $client->loginUser($this->getUser($client, 'demo@trainlog.local'));

        $crawler = $client->request('GET', '/workouts/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Uložit')->form();
        $form['workout[name]'] = 'Functional test workout';
        $form['workout[performedAt]'] = '2026-05-15';
        $form['workout[durationMinutes]'] = '42';

        $client->submit($form);

        self::assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#/workouts/\d+$#', $location);

        // Verify DB
        $em = $client->getContainer()->get('doctrine')->getManager();
        assert($em instanceof EntityManagerInterface);
        $em->clear();
        $persisted = $em->getRepository(Workout::class)->findOneBy(['name' => 'Functional test workout']);
        self::assertInstanceOf(Workout::class, $persisted);
        self::assertSame(42, $persisted->getDurationMinutes());
        self::assertFalse($persisted->isTemplate());
        self::assertSame('demo@trainlog.local', $persisted->getUser()?->getEmail());

        // We inserted a row — mark fixtures dirty so the next test class reseeds.
        $this->markFixturesDirty();
    }
}
