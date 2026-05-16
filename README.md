# TrainLog

Webová aplikace pro vedení tréninkového deníku — semestrální práce VŠE.

## Stack

- **PHP 8.4** + **Symfony 7.3** (FrameworkBundle, SecurityBundle, Form, Doctrine ORM 3, Twig, AssetMapper, Stimulus)
- **MariaDB 10.11** (přes `docker compose`)
- **Bootstrap 5.3** (CSS přes CDN, JS přes AssetMapper)
- **Chart.js 4** (grafy přes Stimulus controller)
- **PHPStan max** + **php-cs-fixer** + **PHPUnit 11** pro kvalitu
- Doctrine **fixtures** pro demo data

## Quick start

Vše běží přes lokální `nix-shell` (viz `shell.nix`) — PHP, Composer i ostatní nástroje
poskytuje Nix.

```bash
# 1. Závislosti
nix-shell --run "composer install"

# 2. Lokální konfigurace (zkopírovat a doplnit APP_SECRET / DB heslo)
cp .env.local.dist .env.local

# 3. Databáze (MariaDB v Dockeru)
docker compose up -d database

# 4. Migrace + demo seed (uživatelé, cviky, 6 týdnů tréninků)
nix-shell --run "php bin/console doctrine:migrations:migrate --no-interaction"
nix-shell --run "composer seed"

# 5. Dev server
nix-shell --run "php -S 127.0.0.1:8123 -t public public/index.php"
```

Otevřete <http://127.0.0.1:8123/> a přihlaste se demo účtem (viz níže).

## Demo credentials (po `composer seed`)

| Role | E-mail | Heslo |
|---|---|---|
| admin | `admin@trainlog.local` | `admin` |
| user | `demo@trainlog.local` | `demo` |
| user | `hosta@trainlog.local` | `demo` |

Demo uživatel `demo@trainlog.local` má v DB ~18 tréninků za posledních 6 týdnů
s progresivním přetížením, takže grafy vývoje a osobní rekordy mají reálná data.

## Databáze

`compose.yaml` definuje MariaDB službu `database`. Připojení (lokální):

- host: `127.0.0.1` (port `3306` mapován)
- DB: `trainlog`
- user / password: `app` / `!ChangeMe!`

Veškerá běžná správa dat probíhá z aplikace (registrace, administrace
uživatelů, cviků a svalových partií). **První registrovaný uživatel
automaticky získá roli administrátora**, takže pro nasazení od nuly stačí
projít `/register` v prohlížeči — žádné CLI ani SQL příkazy nejsou potřeba.

### Pouze pro debug / maintenance

Tyto příkazy nejsou součástí běžného workflow; používejte jen při ladění
nebo havarijních zásazích.

```bash
# Reset DB (zahodí všechna data):
nix-shell --run "php bin/console doctrine:database:drop --force --if-exists"
nix-shell --run "php bin/console doctrine:database:create"
nix-shell --run "php bin/console doctrine:migrations:migrate --no-interaction"

# Přímý mariadb shell (jen pro inspekci dat, ne pro správu):
docker exec semestralka-database-1 sh -c "mariadb -uapp -p'!ChangeMe!' trainlog"

# Načíst jen základní seed (svalové partie) bez demo dat:
nix-shell --run "php bin/console doctrine:fixtures:load --group=default --no-interaction"

# Maintenance: povýšit existujícího uživatele na admina mimo registrační flow
nix-shell --run "php bin/console app:user:promote <email>"
```

## Common commands

```bash
# QA bundle (lint + phpstan + phpunit)
nix-shell --run "composer qa"

# Jednotlivě:
nix-shell --run "composer lint"      # php-cs-fixer --dry-run
nix-shell --run "composer format"    # php-cs-fixer fix
nix-shell --run "composer check"     # phpstan analyse
nix-shell --run "composer test"      # phpunit

# Konzole
nix-shell --run "php bin/console <cmd>"

# Promote uživatele na admina
nix-shell --run "php bin/console app:user:promote <email>"

# Načíst seed:
nix-shell --run "composer seed"
```

PHPStan běží na úrovni `max`; styl `php-cs-fixer` převzat z
<https://github.com/maxa-ondrej/shipmonk-linked-list>.

## Project structure

```
src/
  Command/                    # CLI příkazy (app:user:promote, app:exercise:import-wger)
  Controller/                 # MVC kontrolery (HomeController, ExerciseController, …)
    Admin/                    # /admin/* (RBAC ROLE_ADMIN)
  DataFixtures/               # Demo seed (--group=demo) + muscle groups (default+demo)
  Entity/                     # Doctrine entity (User, Exercise, MuscleGroup, Workout, WorkoutSet, Role)
  Form/                       # FormTypes (ExerciseType, WorkoutType, WorkoutSetType, …)
  Repository/                 # Doctrine repositories
  Security/Voter/             # ExerciseVoter, WorkoutVoter (view/edit/delete)
  Service/                    # PersonalRecordCalculator, WorkoutTemplateInstantiator, Wger/

templates/
  base.html.twig              # Layout, Bootstrap CDN, flash messages
  _navbar.html.twig           # Top nav (Domů / Cviky / Tréninky / Šablony / Admin)
  admin/                      # /admin/* views
  exercise/                   # cvik index/new/edit/show/progress
  workout/                    # trénink index/new/edit/show + _form / _set_row
  template/                   # šablony tréninků
  pdf/                        # tisková podoba pro export do PDF

assets/
  app.js                      # entrypoint (Bootstrap JS + styles)
  controllers/                # Stimulus controllers (collection, chart, …)
  styles/app.css

migrations/                   # Doctrine migrace (zdroj pravdy o schématu)
```

## Schéma — mapování názvů

Kód i migrace používají anglické názvy sloupců, zatímco zadání uvádí jejich české
ekvivalenty. Sémantika je identická — jde čistě o konvenci pojmenování. Níže jsou
uvedeny pouze sloupce, kde se název v kódu liší od názvu v zadání.

| Tabulka | Sloupec (kód) | Sloupec (zadání) |
|---|---|---|
| user | name | jméno |
| exercise | name | název |
| exercise | description | popis |
| muscle_group | name | název |
| workout | performed_at | datum |
| workout | name | název |
| workout | note | poznámka |
| workout | duration_minutes | doba_trvání |
| workout_set | position | pořadí |
| workout_set | reps | opakování |
| workout_set | weight_kg | váha_kg |

## Routes přehled

- **Public**: `/`, `/register`, `/login`
- **User (ROLE_USER)**: `/exercises*`, `/workouts*`, `/templates*`, `/api/exercises/{id}/progress.json`
- **Admin (ROLE_ADMIN)**: `/admin`, `/admin/users`, `/admin/exercises`, `/admin/muscle-groups`

`php bin/console debug:router` zobrazí kompletní výpis.

## Deployment notes

Aplikace je laděna na lokální vývoj. Pro produkci je potřeba:

1. Nahradit MariaDB credentials a JWT/CSRF secrets v `.env.local` (nepřítomný v repu).
2. `composer install --no-dev --optimize-autoloader`.
3. `php bin/console asset-map:compile` (vygeneruje `public/assets/`).
4. Webserver má servovat `public/index.php` jako front controller.
5. Před prvním spuštěním: `doctrine:migrations:migrate` + `doctrine:fixtures:load --group=default` (jen muscle groups, ne demo data).

## License

MIT — viz `composer.json`. Autor: **Ondřej Maxa** ([maxo00@vse.cz](mailto:maxo00@vse.cz)).
