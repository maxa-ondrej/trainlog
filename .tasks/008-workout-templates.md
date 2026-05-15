# 008 — Workout templates

A template is a `Workout` with `is_template = true`. User picks a template
and the app clones its sets into a fresh workout dated today.

## Acceptance criteria

- [ ] `/templates` (GET) — list of the user's templates (+ optionally shared
      templates if we add a `is_public` flag later — out of scope for now).
- [ ] `/templates/new` — same form as a workout but with `is_template = true`.
- [ ] `/workouts/from-template/{id}` (POST) — creates a new non-template
      workout with the same name + cloned sets (position preserved, weight/reps
      copied as a starting point), redirects to its edit page.
- [ ] Templates excluded from the regular `/workouts` history listing.

## Touched files

- `src/Controller/WorkoutController.php`
- `src/Controller/TemplateController.php`
- `src/Service/WorkoutTemplateInstantiator.php`
- `templates/template/*.html.twig`

## Depends on

- 007.
