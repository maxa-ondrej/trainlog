# 007 — Workout CRUD with sets

Record a training session: pick exercises, log multiple sets per exercise with
reps, weight and optional RPE.

## Acceptance criteria

- [ ] `/workouts` (GET) — list of the current user's workouts, newest first.
- [ ] `/workouts/new` (GET/POST) — create workout with embedded `WorkoutSet`
      collection. Form uses `CollectionType` with allow_add / allow_delete and
      a Stimulus controller to add rows dynamically.
- [ ] `/workouts/{id}` (GET) — detail, list of sets grouped by exercise.
- [ ] `/workouts/{id}/edit` (GET/POST) — owner-only.
- [ ] `/workouts/{id}/delete` (POST) — owner-only, cascade deletes sets.
- [ ] Voter `WorkoutVoter` handles permissions.
- [ ] `WorkoutSet.position` auto-numbered server-side on save.

## Touched files

- `src/Controller/WorkoutController.php`
- `src/Form/WorkoutType.php`
- `src/Form/WorkoutSetType.php`
- `src/Security/Voter/WorkoutVoter.php`
- `assets/controllers/collection_controller.js`
- `templates/workout/*.html.twig`

## Depends on

- 002, 004, 006.
