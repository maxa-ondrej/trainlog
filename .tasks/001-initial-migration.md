# 001 — Initial database migration

Generate the first Doctrine migration that creates all six tables backing the
domain entities: `user`, `exercise`, `muscle_group`, `exercise_muscle_group`,
`workout`, `workout_set`.

## Acceptance criteria

- [ ] `docker compose up -d database` brings up MariaDB locally.
- [ ] `php bin/console doctrine:database:create` succeeds.
- [ ] `php bin/console make:migration` produces a single migration covering all
      six tables with the proper FKs and indexes.
- [ ] `php bin/console doctrine:migrations:migrate` applies cleanly on an empty DB.
- [ ] `php bin/console doctrine:schema:validate` reports no diff.

## Touched files

- `migrations/Version20260516xxxxxx.php` (generated)
- possibly minor `src/Entity/*` tweaks if validate complains

## Depends on

- Entities already in place (`src/Entity/*`).
