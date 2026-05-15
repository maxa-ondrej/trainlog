# 012 — Demo fixtures

Seed a usable demo dataset: 1 admin, 2 regular users, ~10 public exercises
covering common lifts, ~6 weeks of workouts for one user so charts have data.

## Acceptance criteria

- [ ] `AdminFixtures` — `admin@trainlog.local` / `admin` with `ROLE_ADMIN`.
- [ ] `UserFixtures` — `demo@trainlog.local` / `demo` plus one extra.
- [ ] `ExerciseFixtures` — bench press, squat, deadlift, OHP, bent-over row,
      pull-up, dip, lunges, leg curl, leg extension (each tagged with muscle
      groups, `is_public = true`, owned by admin).
- [ ] `WorkoutFixtures` — generates ~18 workouts for the demo user over ~6 weeks
      with progressive overload patterns so progress charts look real.
- [ ] Fixtures are loaded via `--group=demo` so prod doesn't accidentally seed.
- [ ] `make seed` (or composer script `seed`) wraps the load command.

## Touched files

- `src/DataFixtures/*.php`
- `composer.json` (`seed` script)

## Depends on

- 001, 005, 006, 007.
