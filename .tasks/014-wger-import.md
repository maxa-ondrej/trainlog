# 014 — Optional: import exercises from wger.de API

Per spec (`Volitelně`). Pull a curated subset of exercises from
https://wger.de/api/v2/exercise/ and import them as public exercises owned by
the admin user.

## Acceptance criteria

- [ ] `php bin/console app:exercise:import-wger [--limit=50] [--language=cs]`
      pulls exercises, maps muscle groups by name (best-effort fuzzy match),
      and upserts into `exercise` with `is_public = true`.
- [ ] HTTP via `Symfony\Contracts\HttpClient\HttpClientInterface`.
- [ ] Network is mocked in a unit test via `MockHttpClient`.
- [ ] Re-running the command is idempotent.

## Touched files

- `src/Command/ImportWgerExercisesCommand.php`
- `src/Service/Wger/WgerClient.php`
- `tests/Service/Wger/WgerClientTest.php`

## Depends on

- 005, 006.
