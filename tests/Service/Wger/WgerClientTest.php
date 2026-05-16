<?php

declare(strict_types=1);

namespace App\Tests\Service\Wger;

use App\Service\Wger\WgerClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @internal
 */
final class WgerClientTest extends TestCase {
    public function testFetchExercisesParsesTranslationsAndMuscles(): void {
        $payload = json_encode([
            'results' => [
                [
                    'translations' => [
                        ['language' => 2, 'name' => 'Bench Press', 'description' => '<p>chest</p>'],
                        ['language' => 11, 'name' => 'Benchpress', 'description' => '<p>prsa</p>'],
                    ],
                    'muscles' => [
                        ['name_en' => 'Pectoralis major'],
                        ['name_en' => 'Triceps brachii'],
                    ],
                ],
                [
                    'translations' => [
                        ['language' => 11, 'name' => 'Dřep', 'description' => 'plain text'],
                    ],
                    'muscles' => [
                        ['name_en' => 'Quadriceps femoris'],
                    ],
                ],
            ],
        ]);
        self::assertIsString($payload);

        $mock = new MockHttpClient(new MockResponse($payload, [
            'response_headers' => ['Content-Type: application/json'],
        ]));

        $client = new WgerClient($mock);
        $result = $client->fetchExercises(2, 'cs');

        self::assertCount(2, $result);
        self::assertSame('Benchpress', $result[0]['name']);
        self::assertSame('prsa', $result[0]['description']);
        self::assertSame(['Pectoralis major', 'Triceps brachii'], $result[0]['muscles']);
        self::assertSame('Dřep', $result[1]['name']);
    }

    public function testFetchExercisesSkipsEntriesWithoutMatchingLanguage(): void {
        $payload = json_encode([
            'results' => [
                [
                    'translations' => [
                        ['language' => 2, 'name' => 'English only'],
                    ],
                    'muscles' => [],
                ],
            ],
        ]);
        self::assertIsString($payload);

        $mock = new MockHttpClient(new MockResponse($payload));
        $client = new WgerClient($mock);

        $result = $client->fetchExercises(10, 'cs');

        self::assertSame([], $result);
    }
}
