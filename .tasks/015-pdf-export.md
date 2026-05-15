# 015 — Optional: PDF export of workout / monthly summary

Per spec (`Volitelně`). Export either a single workout or a monthly summary
to a printable PDF.

## Acceptance criteria

- [ ] `/workouts/{id}/export.pdf` — single-workout PDF, owner-only.
- [ ] `/workouts/export/{year}-{month}.pdf` — monthly summary for the current
      user, with totals + per-exercise breakdown.
- [ ] Uses `dompdf/dompdf` (pure PHP, no system deps) via a thin
      `PdfRenderer` service that takes a Twig template name + context.
- [ ] Templates live in `templates/pdf/*.html.twig` and use a stripped-down
      stylesheet (no Bootstrap JS, print-friendly typography).

## Touched files

- `composer.json` (+ `dompdf/dompdf`)
- `src/Service/PdfRenderer.php`
- `src/Controller/WorkoutExportController.php`
- `templates/pdf/{workout,monthly}.html.twig`

## Depends on

- 007, 009.
