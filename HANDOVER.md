# Session Handover — 2026-05-16 (evening)

## Summary

Closed out the **TrainLog** roadmap from `.tasks/005` through `.tasks/015`,
then iterated on quality, polish, and teacher feedback. The afternoon
session ended at `7e86572` (frontend assets). This session adds 5 commits
on top:

```
<head>  docs: in-app /navod guide, navbar wiring, smoke-test, fresh HANDOVER
f2fd3b9 Polish + teacher feedback (auto-admin, home dashboard, SRI, env template)
4447319 Add GitHub Actions QA workflow
2a65452 Add functional + unit test coverage
4f24d39 Implement TrainLog feature set (tasks 005–015)
7e86572 Wire Bootstrap 5, Stimulus, Chart.js via AssetMapper  ← pre-existing
```

Project is grading-eligible per teacher (`zadání máte schválené`). The
remaining items are nice-to-haves, not blockers.

## What Was Worked On & What Got Done

| Range | Scope | Outcome |
|---|---|---|
| `.tasks/005`–`015` | Full feature set: muscle groups admin, exercise CRUD with voter, workout CRUD with embedded sets + Stimulus collection controller, templates with instantiator, progress chart with Chart.js, personal records, demo fixtures, README, wger import, PDF export | one big commit `4f24d39` |
| `composer qa` | Pre-existing 7 PHPStan max errors paid down, php-cs-fixer clean, 17 PHPUnit tests / 66 assertions all green | bundled in `4f24d39` (entity changes) + `2a65452` (new tests) |
| `.github/workflows/qa.yml` | CI runs `composer qa` against a MariaDB service container on every push / PR to `main` | `4447319` — never executed on real GitHub Actions yet (no remote configured) |
| Polish + teacher feedback | Home dashboard with 5 recent workouts, first-user-auto-admin, admin Edit button on /admin/exercises, Bootstrap CDN SRI hash, `.env.local.dist` | `f2fd3b9` |
| Docs | In-app `/navod` Twig guide (HelpController + sticky-TOC template), navbar wired to all routes including `Návod`, `/navod` public access, SMOKE_TEST.md checklist | `<head>` |

Side effects worth noting:

- **`composer seed` is the canonical setup step.** Truncate mode was
  attempted but MariaDB rejects TRUNCATE on FK-referenced tables (error
  1701); the script stayed on the DELETE-based purge which works for
  back-to-back runs.
- **`WorkoutSetRepository` is no longer `final`.** A test stub needed to
  extend it in `tests/Service/PersonalRecordCalculatorTest.php`. Flagged
  in case anyone wants to revisit by switching to a `final`-friendly
  interface or a real-DB integration test.
- **MariaDB still persists locally.** Same container as the morning
  (`semestralka-database-1`, volume `semestralka_database_data`). The
  `composer seed` resets it cleanly to demo state on demand.
- **Demo creds (post-seed):** `admin@trainlog.local / admin`,
  `demo@trainlog.local / demo` (has ~18 workouts), `hosta@trainlog.local
  / demo`.

## What Worked and What Didn't

### Worked

- **Parallel agents for disjoint scopes.** Tasks 13/14/16/17 (PHPStan
  debt / tests / polish / spec audit) and later 15/18/19 (CI / home
  dashboard / README mapping) were dispatched as parallel general-purpose
  agents with explicit file-scope contracts. Zero merge conflicts on
  reintegration. Bumped throughput ~4×.
- **`composer qa` as a single gate.** With the PHPStan max debt paid
  down, every commit since `4f24d39` ends with a green `composer qa`.
  The CI workflow can therefore just call `composer qa` without bespoke
  pipeline logic.
- **`#[Argument]` and `#[Option]` attributes** on `app:exercise:import-wger`.
  Same modern pattern as the morning's `app:user:promote` — PHPStan max
  narrows types automatically. No `configure()` boilerplate.
- **WgerClient via `HttpClientInterface` + `MockHttpClient`.** Unit test
  asserts JSON parsing without ever hitting wger.de. Idempotency in the
  command itself (skip if name already exists).
- **`assets/controllers/collection_controller.js`** for dynamic
  CollectionType rows. Pure Stimulus, ~30 lines, supports add/remove and
  re-numbers nothing client-side (server re-numbers positions on save).
- **`PersonalRecordCalculator::badgeMapForWorkout()`** — single pass per
  exercise; returns `setId → ['váha', 'objem', '1RM']` map for badge
  rendering. Works on read (no denormalised PR table) — fine at
  student-scale data volumes.
- **dompdf + DejaVu Sans.** Czech diacritics render correctly out of the
  box. The `PdfRenderer` service is one trivial Twig→PDF wrapper; both
  exports (`/workouts/{id}/export.pdf`, `/workouts/export/{YYYY}-{MM}.pdf`)
  share it.

### Didn't work / had to work around

- **`--purge-with-truncate` on `composer seed`** fails on MariaDB with
  FK error 1701. Stayed on DELETE-based purge.
- **`composer.json` had `"check": "phpstan analyse -c phpstan.neon"`**
  but the repo file is `phpstan.dist.neon`. Worked locally only by
  accident; was about to break CI. Changed to bare `phpstan analyse`.
- **Stateless CSRF** still blocks `curl`-driven POST testing. The new
  WebTestCase suites use the DOM crawler, which handles it correctly.
  HTTP-level smoke testing in the dev loop has to be GET-only.
- **`Doctrine\ORM\Tools\Pagination\Paginator<T>` generics** are
  unsatisfiable in this codebase without `assert()` / `@var` (both
  forbidden by the project's PHPStan policy). Refactored
  `ExerciseRepository` to expose `findVisiblePage()` + `countVisible()`
  instead of a Paginator return.

## Key Decisions Made and Why

1. **Uninitialized typed properties for non-null FK columns.** Picked
   over constructor-promoted requirement to avoid touching ~10 callsites
   for `new Exercise() / new Workout() / new WorkoutSet()`. Setters
   guarantee they're populated before flush; reading before set returns
   `null` via `$this->prop ?? null`. Minimum blast radius.
2. **`PersonalRecord` computed at read time.** No denormalised table.
   Cost: every detail-page render does one extra query per distinct
   exercise. Benefit: PRs auto-recompute on delete/edit with no
   bookkeeping. Fine for student-scale data; revisit if `n > 10⁴`.
3. **Epley estimate as the 1RM proxy.** `weight × (1 + reps/30)`. Cited
   widely; close enough for the chart's purpose without a config knob.
4. **`composer seed` uses DELETE purge, not TRUNCATE.** See above —
   TRUNCATE is FK-incompatible on MariaDB. DELETE is slower but correct.
5. **First registered user auto-promoted to ROLE_ADMIN.** Removes the
   CLI-only initial-admin path that the teacher flagged. Implemented in
   `RegistrationController::register` with `UserRepository::count([]) === 0`.
   The `app:user:promote` command stays as a maintenance fallback.
6. **`/navod` is public.** Anonymous users can read the guide so they
   can decide if the app is what they want before registering. Listed
   in `security.yaml` access_control as `PUBLIC_ACCESS`.
7. **README written before `composer seed` script tested.** When CI was
   added, the bad `phpstan analyse -c phpstan.neon` line was caught
   by the CI-authoring agent. Fixed up in commit `f2fd3b9`. Lesson:
   always run `composer qa` literally — not just the individual
   components — before claiming green.
8. **Five thematic commits** (this session) rather than per-task
   commits (morning style). Per-task would have required ~18 commits
   with significant partial-staging gymnastics. The thematic groupings
   read cleaner in `git log --oneline`:
   - tasks 005–015 (feature set)
   - functional + unit tests
   - GitHub Actions CI
   - polish + teacher feedback
   - in-app guide + smoke-test + handover
9. **In-app `/navod` instead of `NAVOD.md`** at the user's request. A
   real Twig page is reachable from the navbar by both authenticated
   and anonymous users; markdown would have lived in the repo only.

## Lessons Learned & Gotchas

- **PHPStan-doctrine's `doctrine.associationType`** wants the *PHP*
  property type to match the column's nullability *exactly*. A
  non-nullable column needs a non-nullable property (PHP-typed). Setter
  signature doesn't matter; only the property does.
- **`@var` annotation override** is banned by the project's PHPStan
  policy. So is `assert()` for narrowing. Either fix the upstream type,
  refactor to make the type obvious, or accept slightly weaker generics.
- **`final` on `WorkoutSetRepository`** blocked stubbing in
  `PersonalRecordCalculatorTest`. PHPUnit can't mock final classes.
  Removed `final`. Alternative would have been an integration test that
  hits the real DB — overkill for what's a pure-aggregation calculator.
- **Symfony 7.3 `CollectionType` allow_add prototype.** The Stimulus
  controller reads `data-collection-prototype-value` and replaces
  `__name__` with an incrementing index. Make sure the prototype value
  is HTML-escaped (`|e('html_attr')` in Twig) — otherwise nested form
  attributes break the outer attribute parsing.
- **Spec PDF / migration column-name divergence.** Czech spec calls the
  columns `datum`, `poznámka`, `doba_trvání`; migration uses English
  (`performed_at`, `note`, `duration_minutes`). README has a mapping
  table so a grader doesn't ding the divergence. Semantics identical.
- **`--purge-with-truncate` ≠ free lunch on MariaDB.** MariaDB rejects
  TRUNCATE on FK-referenced tables (error 1701). Use DELETE.
- **CI workflow not yet validated.** `.github/workflows/qa.yml` looks
  right but has never run on real GitHub Actions — no remote configured.
  First push will reveal env quirks (MariaDB health-check timing,
  composer install timing, etc.).

## Current State

### Working

- `git log --oneline` shows the 5 new commits on `main`.
- `nix-shell --run "composer qa"` → green:
  - php-cs-fixer dry-run: 0 diffs
  - PHPStan max: 0 errors
  - PHPUnit: 17 tests / 66 assertions / 0 failures
- `nix-shell --run "php bin/console lint:twig templates/"` → 29 files OK.
- `composer seed` → admin + 2 users + 11 muscle groups + 10 exercises +
  18 workouts + 198 sets.
- `php -S 127.0.0.1:8123 -t public public/index.php` serves the app;
  all 24+ routes resolve correctly (smoke-tested at HTTP level).
- Dev server is **currently running in background** (PID owned by the
  shell that started it; stop with `pkill -f "php -S 127.0.0.1:8123"`).

### Broken / partial

- **`.github/workflows/qa.yml` never executed on real GitHub Actions.**
  No remote configured. Will need to be `git remote add origin …` +
  `git push -u origin main` before CI does anything.
- **Browser smoke test in progress.** SMOKE_TEST.md exists with a
  ~13-section checklist; user was running through it when this commit
  set landed. Outcome not yet reported back. If anything failed, it'll
  surface as new tasks.
- **No `git tag` for the submission point.** Once CI is green and the
  browser smoke test passes, a `v1.0-submission` tag would mark the
  graded snapshot.

### Temporary hacks / TODOs

- `WorkoutSetRepository` is non-`final` to enable test stubbing
  (`f2fd3b9`'s base commit, technically `4f24d39`). Revisit if you want
  the class final again.
- Bootstrap CDN SRI hash is hard-pinned to `5.3.8`. If you upgrade
  Bootstrap, recompute the hash:
  `curl https://cdn.jsdelivr.net/npm/bootstrap@<ver>/dist/css/bootstrap.min.css |
   openssl dgst -sha384 -binary | openssl base64 -A`.
- `templates/admin/index.html.twig` still has `Cviky` linking to
  `admin_exercise_index` — fine, but a future refactor that splits user
  vs admin exercise lists should revisit.

## Clear Next Steps

In rough priority order, after the user finishes the in-flight browser
smoke test:

1. **Configure a git remote and push.** `git remote add origin <url>` +
   `git push -u origin main`. Watch the GitHub Actions run. Fix any CI
   quirks that surface (likely a 1-line tweak to `qa.yml`).
2. **Tag a submission snapshot.** Once CI is green and the browser test
   passes, `git tag -a v1.0-submission -m "Semester project submission"`
   + `git push origin v1.0-submission`.
3. **Optional: functional tests for the new flows.** Auto-admin and
   `/navod` aren't yet covered by `tests/Functional/`. ~30 min.
   - `SecurityControllerTest::testFirstUserBecomesAdmin` — register on
     an empty user table, assert role + flash.
   - `HelpControllerTest::testGuideRendersAnonymouslyAndLoggedIn`.
4. **Optional: spec polish from #17.** Implementation matches the
   spec's required scope; the items flagged (extra 1RM metric on the
   progress chart, three PR kinds vs the spec's vague "personal
   records") are value-adds, not gaps.

## Important Files Map

### New this session

```
src/Controller/ExerciseController.php
src/Controller/ExerciseProgressController.php
src/Controller/TemplateController.php
src/Controller/WorkoutController.php
src/Controller/WorkoutExportController.php
src/Controller/HelpController.php
src/Controller/Admin/MuscleGroupController.php
src/Command/ImportWgerExercisesCommand.php
src/DataFixtures/{Admin,Exercise,MuscleGroup,User,Workout}Fixtures.php
src/Form/{Exercise,MuscleGroup,Workout,WorkoutSet}Type.php
src/Security/Voter/{Exercise,Workout}Voter.php
src/Service/PdfRenderer.php
src/Service/PersonalRecordCalculator.php
src/Service/WorkoutTemplateInstantiator.php
src/Service/Wger/WgerClient.php
assets/controllers/{chart,collection}_controller.js
templates/admin/muscle_group/{index,new,edit}.html.twig
templates/exercise/{_form,index,new,edit,show,progress}.html.twig
templates/workout/{_form,_set_row,index,new,edit,show}.html.twig
templates/template/{index,new}.html.twig
templates/pdf/{_layout,workout,monthly}.html.twig
templates/help/index.html.twig
tests/Functional/{FixturesWebTestCase,SecurityControllerTest,ExerciseControllerTest,WorkoutControllerTest}.php
tests/Service/{PersonalRecordCalculatorTest,WorkoutTemplateInstantiatorTest,Wger/WgerClientTest}.php
.github/workflows/qa.yml
.env.local.dist
README.md
SMOKE_TEST.md
```

### Modified this session

```
src/Entity/{Exercise,Workout,WorkoutSet,User,MuscleGroup}.php   # PHPStan-max compliance + uninitialized typed props
src/Repository/{Exercise,Workout,WorkoutSet}Repository.php      # paginated visible / user history / progress aggregation
src/Controller/HomeController.php                               # dashboard with 5 recent workouts
src/Controller/RegistrationController.php                       # first-user auto-admin
src/Controller/Admin/{Exercise,User}Controller.php              # minor (cs-fixer)
templates/_navbar.html.twig                                     # all nav links wired + /navod entry
templates/admin/exercises/index.html.twig                       # Upravit button per row
templates/admin/index.html.twig                                 # link to /admin/muscle-groups
templates/base.html.twig                                        # Bootstrap CDN SRI hash
templates/home/index.html.twig                                  # dashboard variant
config/packages/security.yaml                                   # /navod PUBLIC_ACCESS
composer.json + composer.lock                                   # dompdf, seed script, fixed check script
phpstan.dist.neon                                               # removed unused tests/ ignore
tests/bootstrap.php                                             # dropped always-true method_exists branch
importmap.php                                                   # transitive deps pulled by 005-015 work
```

### Entry points & quick commands

```bash
# Dev server
nix-shell --run "php -S 127.0.0.1:8123 -t public public/index.php"

# Console
nix-shell --run "php bin/console <cmd>"

# QA bundle (green as of <head>)
nix-shell --run "composer qa"

# Reset to demo state
nix-shell --run "composer seed"

# Promote a user to admin (CLI fallback if you didn't get auto-admin)
nix-shell --run "php bin/console app:user:promote <email>"
```

### Demo credentials (post `composer seed`)

| Role | E-mail | Heslo |
|---|---|---|
| admin | `admin@trainlog.local` | `admin` |
| user (has ~18 workouts, 6 weeks) | `demo@trainlog.local` | `demo` |
| user (empty, for 403 cross-user tests) | `hosta@trainlog.local` | `demo` |

### Author / metadata

Unchanged:
- `composer.json` author: **Ondřej Maxa, maxo00@vse.cz**
- Spec PDF: `/Users/ondrej.maxa/Downloads/zadani TrainLog.pdf`
- Style reference: <https://github.com/maxa-ondrej/shipmonk-linked-list>
