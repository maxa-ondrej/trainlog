# 002 — Auth: registration + login

Wire Symfony Security to the `User` entity, replace the in-memory provider with
an entity provider, and add a registration form + form-login flow.

## Acceptance criteria

- [ ] `config/packages/security.yaml` uses `entity` provider on `App\Entity\User`
      with `property: email`.
- [ ] `form_login` configured on the `main` firewall with `login_path` /
      `check_path` routes.
- [ ] `RegistrationController` with `/register` GET+POST renders a form, hashes
      the password via `UserPasswordHasherInterface`, persists the user with
      role `ROLE_USER`, and logs them in.
- [ ] `SecurityController::login` + `/logout` route configured.
- [ ] Passwords hashed with `auto` algorithm (already in skeleton).
- [ ] CSRF tokens on both forms.

## Touched files

- `config/packages/security.yaml`
- `src/Controller/RegistrationController.php`
- `src/Controller/SecurityController.php`
- `src/Form/RegistrationFormType.php`
- `templates/registration/register.html.twig`
- `templates/security/login.html.twig`

## Depends on

- 001 (DB schema must exist so users can be persisted).
