# Session Handover — 2026-05-16

## Summary

Bootstrapped a new Symfony 7.3 project for **TrainLog**, a training-diary web app
(school semestral project; spec PDF at `/Users/ondrej.maxa/Downloads/zadani TrainLog.pdf`).
Set up the project skeleton, QA tooling (PHPStan max + php-cs-fixer mirroring
[shipmonk-linked-list](https://github.com/maxa-ondrej/shipmonk-linked-list)),
all six Doctrine entities, a hello-world homepage that boots cleanly, and a
`.tasks/` directory enumerating the remaining roadmap. Project lives at
`/Users/ondrej.maxa/vse/webovky/semestralka/`. **Not yet a git repository.**

## What Was Worked On & What Got Done

| # | Item | Status |
|---|---|---|
| 1 | Symfony 7 skeleton via `composer create-project symfony/skeleton` | done |
| 2 | Project-local PHP 8.4 via `shell.nix` (Nix, not Homebrew — user choice) | done |
| 3 | `composer.json` rewritten with full dep list + QA scripts (`lint`, `format`, `check`, `test`, `qa`) | done |
| 4 | `phpstan.dist.neon` at level `max` with `phpstan-symfony` + `phpstan-doctrine` includes | done |
| 5 | `.php-cs-fixer.dist.php` ported from shipmonk-linked-list, adapted to a Symfony project (excludes `var/`, `vendor/`, `public/bundles`, `migrations`, `config/bundles.php`, `config/preload.php`, `config/reference.php`) | done |
| 6 | DB swapped Postgres → MariaDB in `.env`, `compose.yaml`, `compose.override.yaml` | done |
| 7 | All six entities: `User`, `Exercise`, `MuscleGroup`, `Workout`, `WorkoutSet`, plus `Role` enum and matching repository stubs | done |
| 8 | `HomeController` + `templates/home/index.html.twig` rendering "Hello, world!" | done — `GET /` returns `200 OK` |
| 9 | `.tasks/001-…-015-….md` task files for remaining roadmap | done |

Tasks **not** started in this session (planned in `.tasks/`):

- Initial DB migration (`.tasks/001`)
- Auth: registration + login (`.tasks/002`) — still using skeleton's `users_in_memory` provider
- Admin section / RBAC (`.tasks/003`)
- Bootstrap 5 + Stimulus + Chart.js asset wiring (`.tasks/004`)
- Muscle-group seed + admin (`.tasks/005`)
- Exercise CRUD with filters (`.tasks/006`)
- Workout CRUD (`.tasks/007`)
- Workout templates (`.tasks/008`)
- Workout history (`.tasks/009`)
- Progress chart per exercise (`.tasks/010`)
- Personal records (`.tasks/011`)
- Demo fixtures (`.tasks/012`)
- README (`.tasks/013`)
- Optional wger.de import (`.tasks/014`)
- Optional PDF export (`.tasks/015`)

## What Worked and What Didn't

### Worked

- **`nix-shell` for PHP 8.4**: created `shell.nix` pinning `php84` (8.4.21) +
  composer + symfony-cli + nodejs_22, with the extensions Symfony+MariaDB need
  (`pdo_mysql intl zip mbstring opcache sodium xsl gd bcmath`). All later
  `composer` / `php` / `bin/console` invocations run as `nix-shell --run "..."`.
- **Booting the dev server** via `php -S 127.0.0.1:8123 -t public public/index.php`
  inside the nix-shell, with `run_in_background: true`. The standard
  `until curl -sf … ; do sleep 1; done` loop confirmed readiness.
- **php-cs-fixer auto-fix on skeleton files**: the skeleton emits files without
  `declare(strict_types=1)` and with Symfony brace style; running
  `vendor/bin/php-cs-fixer fix` once normalised them to the shipmonk style
  (declare strict, same-line braces, arrow functions where possible).

### Didn't work

- **Symfony 7.2.x pin** — initial `composer.json` pinned `symfony/* 7.2.*`, but
  Composer refused with security advisories `PKSA-365x-2zjk-pt47` and
  `PKSA-b35n-565h-rs4q` blocking `symfony/http-foundation`. Fixed by switching
  all Symfony constraints to `^7.3` and the Flex `extra.symfony.require` to
  `^7.3`.
- **Homebrew suggestion** — I started typing `brew install php` and got
  interrupted; user wanted Nix instead. Don't reach for `brew` on this machine.
- **Stray `enable_native_lazy_objects: true`** — accidentally added to
  `config/packages/doctrine.yaml`; reverted in the same step.
- **First php-cs-fixer run failed** with `config/reference.php` (the
  auto-generated Symfony config reference, 1500+ lines) producing a diff.
  Fixed by adding it to the `notPath` exclusion list.

## Key Decisions Made and Why

1. **PHP 8.4 via project-local `shell.nix`, not a global install.** User runs
   Determinate Nix and explicitly rejected Homebrew. A project-local shell.nix
   means the project ships with its own PHP version + extensions; no machine
   pollution. Tradeoff: every PHP/composer command must be prefixed
   `nix-shell --run "..."`.
2. **Symfony 7.3 over 7.2**, see above re: security advisories.
3. **MariaDB over Postgres.** Spec says MySQL/MariaDB. Swapped the skeleton's
   Postgres compose service + DSN.
4. **`is_template: bool` flag on `Workout`** instead of a separate template
   table. The spec lists exactly 6 tables; templates fit cleanly as a flag on
   `workout`, matching the spec literally. New workouts can be cloned from
   templates (see `.tasks/008`).
5. **`Role` as a PHP enum + derived `getRoles()`.** Spec says "role" (singular).
   Stored as `enumType: Role::class` (single column). `getRoles()` returns
   `['ROLE_ADMIN', 'ROLE_USER']` for admins so Symfony Security's hierarchical
   expectations still work.
6. **`WorkoutSet.position` instead of `order`** — `order` is a reserved SQL
   keyword, friction not worth it.
7. **`weightKg` and `rpe` as DECIMAL strings**, not floats. Doctrine returns
   `decimal` as string by default; preserves precision, dodges float-equality
   landmines. Added `getWeightKgAsFloat()` and `getVolume()` helpers.
8. **Repository stubs created upfront** — `repositoryClass: …` attributes on
   entities make Doctrine resolve those classes; missing classes fail at first
   `getRepository()` call. Empty stubs unblock task 001 (migration generation)
   and 006/007 (where queries will live).
9. **`.tasks/NNN-name.md` numbering scheme** — three-digit prefix lets us insert
   later (`016`, `017`…) without renumbering. Each file has a fixed shape:
   *Title — Acceptance criteria — Touched files — Depends on*.
10. **AssetMapper (no Node toolchain in production).** Skeleton already
    includes it; tasks build on that. Chart.js + Bootstrap pulled via
    `importmap:require`. CDN for Bootstrap CSS is the suggested shortcut.

## Lessons Learned & Gotchas

- **`nix-shell` cold-starts**. First `nix-shell --run` after editing
  `shell.nix` re-evaluates and may take a minute. Subsequent invocations are
  fast (cached store paths). The Determinate Nix install here is version
  3.15.2 / nix 2.33.1.
- **PHP `composer.json` `config.platform.php`** is set to `8.4.21` (matches
  nix-shell). If shell.nix is bumped to a newer 8.4.x, bump this too or
  composer will resolve against the wrong target.
- **`auto-scripts` in `composer.json`** currently invokes
  `cache:clear`, `assets:install %PUBLIC_DIR%`, `importmap:install`. The
  last one is safe even before `importmap.php` is fully populated.
- **php-cs-fixer warning**: it runs on all PHP files in the repo. Always
  re-check `notPath` after adding generated files (e.g. future
  `config/reference.php` regenerations after `cache:warmup`).
- **Doctrine + the `user` table name** — `User.php` uses `#[ORM\Table(name: '`user`')]`
  (backtick-escaped) because `user` is reserved in Postgres and a soft-reserved
  word in some MySQL contexts. Backticks survive in DDL.
- **`enumType: Role::class`** is a Doctrine 3 feature. Already on the project
  (composer requires `doctrine/orm: ^3.3`).
- **Skeleton ships a `tests/bootstrap.php`** that uses `(new Dotenv())->bootEnv(...)`.
  php-cs-fixer rewrote it to `new Dotenv()->bootEnv(...)` (PHP 8.4 new-without-parens).
  Looks weird but is valid.
- **Background dev server** — `kill %1` doesn't reach the nix-shell child;
  used `pkill -f "php -S 127.0.0.1:8123"`. The background task ID
  `bztotqz4h` reported `failed exit 144` after kill — that's expected.
- **The project is not a git repo yet.** `git init` was deliberately not run.
  Do this early in the next session if the user wants version control.

## Current State

### Working

- `nix-shell --run "php --version"` → PHP 8.4.21
- `nix-shell --run "composer install"` → all deps installed, lockfile present
- `nix-shell --run "php -S 127.0.0.1:8123 -t public public/index.php"`
  + `curl http://127.0.0.1:8123/` → 200 OK with the TrainLog homepage
- `nix-shell --run "vendor/bin/php-cs-fixer fix"` → clean (no diffs)
- All entities + repositories load (Twig + AssetMapper + UX-Turbo + Stimulus
  bundle wired by Flex; importmap renders in the homepage HTML)
- Web profiler + Symfony web debug toolbar are active in dev

### Broken / partial

- **Security still uses `users_in_memory`**. There is no `/login`,
  `/register`, or `/logout` route. `User` entity exists but is not connected
  to the security provider. (Task `.tasks/002`.)
- **No DB exists** — `.env` points at MariaDB on `127.0.0.1:3306` but no
  `docker compose up` has been run, and no migrations exist in
  `migrations/`. Anything that hits the DB will fail. (Task `.tasks/001`.)
- **PHPStan not yet executed** at level max — config is in place but never
  run; expect some warnings on first run (typically around the
  `?User` getters that return null only before persistence).
- **No tests beyond the empty `tests/` directory.**

### Temporary hacks / TODOs

- None hidden in code. Everything provisional is captured either here or in
  `.tasks/*.md`.

## Clear Next Steps

In order of dependency:

1. **`git init` + initial commit** (5 min). The project is currently a pile of
   untracked files. Suggest `.gitignore` already has the Symfony defaults.
2. **`.tasks/001` — initial migration** (15 min):
   ```bash
   nix-shell --run "docker compose up -d database"
   nix-shell --run "php bin/console doctrine:database:create"
   nix-shell --run "php bin/console make:migration"
   nix-shell --run "php bin/console doctrine:migrations:migrate -n"
   nix-shell --run "php bin/console doctrine:schema:validate"
   ```
3. **`.tasks/002` — auth**. Swap `users_in_memory` for entity provider on
   `App\Entity\User` with `property: email`, add `RegistrationController`,
   `SecurityController`, login template. Use `make:registration-form`
   + `make:auth` as starting points (MakerBundle is installed).
4. **`.tasks/004` — frontend assets** in parallel with auth so the navbar +
   forms are styled (`importmap:require bootstrap @popperjs/core chart.js`).
5. **`.tasks/003` — admin section** once auth + role checks are in place.
6. **`.tasks/005` → `.tasks/007`** for the core domain CRUD.
7. **`.tasks/008` → `.tasks/011`** for the higher-value features (templates,
   history, chart, PRs).
8. **`.tasks/012` fixtures** so the chart from `.tasks/010` has data to render.
9. **`.tasks/013` README** last, when commands are stable.
10. **Optional**: `.tasks/014` wger import, `.tasks/015` PDF export.

Before merging anything, run `composer qa` (`lint` + `check` + `test`) and
expect to iterate on PHPStan errors at level max — that bar is intentional.

## Important Files Map

```
/Users/ondrej.maxa/vse/webovky/semestralka/
├── shell.nix                          # Nix dev-shell: PHP 8.4.21 + composer + symfony-cli + node22
├── composer.json                      # Custom name (maxa-ondrej/trainlog), email maxo00@vse.cz,
│                                      # platform.php=8.4.21, scripts: lint/format/check/test/qa
├── phpstan.dist.neon                  # level: max, includes phpstan-symfony + phpstan-doctrine,
│                                      # ignores argument.type errors in tests/
├── .php-cs-fixer.dist.php             # Shipmonk-linked-list rules: @PhpCsFixer, @PHP84Migration,
│                                      # @PhpCsFixer:risky, @PHP82Migration:risky, same-line braces,
│                                      # global namespace imports. Excludes generated configs.
├── .env                                # DATABASE_URL → mariadb 10.11 on :3306/trainlog
├── compose.yaml                       # MariaDB 10.11 service
├── compose.override.yaml              # Exposes 3306:3306 in dev
│
├── src/Entity/
│   ├── Role.php                       # enum Role: User | Admin (string-backed)
│   ├── User.php                       # UserInterface + PasswordAuthenticatedUserInterface,
│   │                                  # email (unique), name, role enum, createdAt,
│   │                                  # OneToMany Workouts, OneToMany Exercises (as owner)
│   ├── MuscleGroup.php                # name (unique), ManyToMany Exercise (inverse side)
│   ├── Exercise.php                   # name, description, owner→User, isPublic,
│   │                                  # ManyToMany MuscleGroup (owning, table exercise_muscle_group),
│   │                                  # OneToMany WorkoutSet
│   ├── Workout.php                    # user, performedAt (date_immutable), name, note,
│   │                                  # durationMinutes (nullable int), isTemplate,
│   │                                  # OneToMany WorkoutSet cascade persist+remove orphanRemoval,
│   │                                  # ordered by position ASC
│   └── WorkoutSet.php                 # workout, exercise, position, reps, weightKg (DECIMAL string),
│                                      # rpe (DECIMAL 3,1 nullable), getVolume() helper
│
├── src/Repository/
│   ├── UserRepository.php             # ServiceEntityRepository<User> — stub
│   ├── ExerciseRepository.php         # stub
│   ├── MuscleGroupRepository.php      # stub
│   ├── WorkoutRepository.php          # stub
│   └── WorkoutSetRepository.php       # stub
│
├── src/Controller/
│   └── HomeController.php             # GET / → home/index.html.twig
│
├── templates/
│   ├── base.html.twig                 # lang=cs, viewport meta, importmap('app'), 🏋️ favicon
│   └── home/index.html.twig           # "Hello, world!" landing
│
├── config/packages/
│   ├── doctrine.yaml                  # auto_mapping App entities at src/Entity, naming
│   │                                  # strategy underscore_number_aware, savepoints on
│   ├── security.yaml                  # *** still users_in_memory — switch in task 002 ***
│   └── (others — skeleton defaults)
│
└── .tasks/
    ├── 001-initial-migration.md
    ├── 002-auth-registration.md
    ├── 003-admin-rbac.md
    ├── 004-frontend-assets.md
    ├── 005-muscle-groups.md
    ├── 006-exercise-crud.md
    ├── 007-workout-crud.md
    ├── 008-workout-templates.md
    ├── 009-workout-history.md
    ├── 010-progress-chart.md
    ├── 011-personal-records.md
    ├── 012-fixtures.md
    ├── 013-readme.md
    ├── 014-wger-import.md
    └── 015-pdf-export.md
```

### Entry points

- HTTP: `public/index.php` (front controller)
- CLI: `bin/console` (Symfony console — always via `nix-shell --run "php bin/console …"`)
- Spec PDF: `/Users/ondrej.maxa/Downloads/zadani TrainLog.pdf` (Czech)
- Style reference: <https://github.com/maxa-ondrej/shipmonk-linked-list>
  (`.php-cs-fixer.dist.php`, `phpstan.neon`, `composer.json` — all read at
  start of session and cached at `/tmp/trainlog-ref/`)

### Author / metadata

- `composer.json` author: **Ondřej Maxa, maxo00@vse.cz**
- User signed-in identity in this environment: `ondrej.maxa@shipmonk.com`
  (different from the project author email — use `maxo00@vse.cz` in
  project files, **not** the shipmonk address).
