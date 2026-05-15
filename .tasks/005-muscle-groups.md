# 005 — Muscle groups: seed + admin management

Seed the `muscle_group` table with the common Czech-named partitions and
provide minimal admin-only CRUD.

## Acceptance criteria

- [ ] `AppFixtures` (or dedicated `MuscleGroupFixtures`) inserts: prsa, záda,
      ramena, biceps, triceps, předloktí, kvadricepsy, hamstringy, hýždě, lýtka,
      břicho.
- [ ] `php bin/console doctrine:fixtures:load --append` is idempotent for these
      seeds (use upsert-style check).
- [ ] `AdminMuscleGroupController` allows admins to add/rename/delete groups.

## Touched files

- `src/DataFixtures/MuscleGroupFixtures.php`
- `src/Controller/Admin/MuscleGroupController.php`
- `templates/admin/muscle_group/*.html.twig`

## Depends on

- 001, 003.
