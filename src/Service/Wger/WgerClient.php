<?php

declare(strict_types=1);

namespace App\Service\Wger;

use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_string;

final class WgerClient {
    private const BASE = 'https://wger.de/api/v2';

    public function __construct(
        private readonly HttpClientInterface $http,
    ) {}

    /**
     * Fetch up to $limit translated exercises in the given language code (e.g. 'cs', 'en').
     * Returns simplified array entries.
     *
     * @return list<array{name: string, description: string, muscles: list<string>}>
     */
    public function fetchExercises(int $limit, string $languageCode): array {
        $response = $this->http->request('GET', self::BASE.'/exerciseinfo/', [
            'query' => [
                'limit' => $limit,
                'language' => self::languageId($languageCode),
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $data = $response->toArray();
        $results = is_array($data['results'] ?? null) ? $data['results'] : [];

        $out = [];
        foreach ($results as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $name = '';
            $description = '';
            $translations = is_array($entry['translations'] ?? null) ? $entry['translations'] : [];
            foreach ($translations as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $lang = $t['language'] ?? null;
                if ($lang === self::languageId($languageCode)) {
                    $name = is_string($t['name'] ?? null) ? $t['name'] : '';
                    $description = is_string($t['description'] ?? null) ? strip_tags($t['description']) : '';

                    break;
                }
            }
            if ($name === '') {
                continue;
            }

            $muscles = [];
            $musclesRaw = is_array($entry['muscles'] ?? null) ? $entry['muscles'] : [];
            foreach ($musclesRaw as $muscle) {
                if (is_array($muscle) && is_string($muscle['name_en'] ?? null)) {
                    $muscles[] = $muscle['name_en'];
                } elseif (is_array($muscle) && is_string($muscle['name'] ?? null)) {
                    $muscles[] = $muscle['name'];
                }
            }

            $out[] = [
                'name' => $name,
                'description' => $description,
                'muscles' => $muscles,
            ];
        }

        return $out;
    }

    private static function languageId(string $code): int {
        return match (strtolower($code)) {
            'cs' => 11,
            'en' => 2,
            'de' => 1,
            default => 2,
        };
    }
}
