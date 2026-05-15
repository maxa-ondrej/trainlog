# 006 — Exercise CRUD with filters

User-facing CRUD over the exercise library. Each user manages their own
exercises and can browse + reuse public ones.

## Acceptance criteria

- [ ] `/exercises` (GET) — paginated list of: my exercises + all public
      exercises. Filterable by muscle group (multi-select).
- [ ] `/exercises/new` (GET/POST) — create form. Authenticated users only.
- [ ] `/exercises/{id}` (GET) — detail view, shows muscle groups + recent uses.
- [ ] `/exercises/{id}/edit` (GET/POST) — editable only by owner (or admin).
- [ ] `/exercises/{id}/delete` (POST) — owner-only, blocked if referenced by
      any `WorkoutSet` (show friendly error).
- [ ] Voter `ExerciseVoter` handles `view` / `edit` / `delete` permissions.
- [ ] Form uses `EntityType` for muscle groups with multi-select + Bootstrap.

## Touched files

- `src/Controller/ExerciseController.php`
- `src/Form/ExerciseType.php`
- `src/Security/Voter/ExerciseVoter.php`
- `src/Repository/ExerciseRepository.php` (add filter query)
- `templates/exercise/{index,new,show,edit,_form}.html.twig`

## Depends on

- 002, 004, 005.
