# 010 — Progress chart per exercise (Chart.js)

Render a line chart of max weight (and/or total volume) over time for a chosen
exercise.

## Acceptance criteria

- [ ] `/exercises/{id}/progress` (GET) — page with chart + metric toggle
      (Max weight / Volume / Estimated 1RM via Epley).
- [ ] `/api/exercises/{id}/progress.json` returns
      `{ labels: ['2026-04-12', ...], maxWeight: [...], volume: [...] }` for the
      logged-in user.
- [ ] Stimulus controller `chart_controller.js` reads the JSON URL from a
      `data-*` attribute and renders a Chart.js line chart.
- [ ] Owner-only (data is per user).

## Touched files

- `src/Controller/ExerciseProgressController.php`
- `src/Repository/WorkoutSetRepository.php` (aggregation query)
- `assets/controllers/chart_controller.js`
- `templates/exercise/progress.html.twig`

## Depends on

- 004 (Chart.js wired), 007.
