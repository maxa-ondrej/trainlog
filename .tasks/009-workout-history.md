# 009 — Workout history + detail

Polish the history view: filtering, summary stats, drill-down.

## Acceptance criteria

- [ ] `/workouts` accepts `from` / `to` date filters + `exercise` filter.
- [ ] Each row shows: date, name, exercise count, total sets, total volume (kg).
- [ ] Detail page shows the full set breakdown + per-exercise summary
      (max weight, top set, total volume).
- [ ] Empty-state message + CTA to create a workout / start from template.

## Touched files

- `src/Controller/WorkoutController.php`
- `src/Repository/WorkoutRepository.php`
- `templates/workout/{index,show}.html.twig`

## Depends on

- 007.
