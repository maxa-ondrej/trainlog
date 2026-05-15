# 011 — Personal records highlighting

Highlight sets that are personal records for that exercise + user.

## Acceptance criteria

- [ ] PR definitions: `max_weight` (heaviest single set, regardless of reps),
      `max_volume_set` (heaviest reps × weight), `max_estimated_1rm` (Epley).
- [ ] `WorkoutSetRepository::findPersonalRecords(User, Exercise): array`
      returns the PR set per category.
- [ ] In `/workouts/{id}` detail and `/exercises/{id}` recent sets, PR rows
      get a Bootstrap badge (🏆 PR — váha / objem / 1RM).
- [ ] Computed at read time (no denormalized table) — fine for student-scale
      data volumes.

## Touched files

- `src/Repository/WorkoutSetRepository.php`
- `src/Service/PersonalRecordCalculator.php`
- `templates/workout/show.html.twig`
- `templates/exercise/show.html.twig`

## Depends on

- 007.
