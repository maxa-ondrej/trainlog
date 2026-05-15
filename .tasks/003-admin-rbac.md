# 003 — Admin section & RBAC

Add `/admin` area protected by `ROLE_ADMIN` with CRUD over users and public
exercises.

## Acceptance criteria

- [ ] `access_control` entry: `{ path: ^/admin, roles: ROLE_ADMIN }`.
- [ ] `AdminUserController`: list users, toggle role (USER ↔ ADMIN), delete user.
- [ ] `AdminExerciseController`: list all exercises across users, toggle
      `is_public`, delete public exercises.
- [ ] Navigation hides `/admin` link for non-admins.
- [ ] First user can be promoted via a CLI command:
      `php bin/console app:user:promote <email>`.

## Touched files

- `config/packages/security.yaml`
- `src/Controller/Admin/*.php`
- `src/Command/PromoteUserCommand.php`
- `templates/admin/*.html.twig`

## Depends on

- 002 (auth working).
