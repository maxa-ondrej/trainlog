# Browser smoke test — TrainLog

Step-by-step manual checklist. Run after a fresh `composer seed` to verify the
whole stack works end-to-end. Stateless CSRF means this can't be automated with
`curl` — use a real browser. Allow ~15 minutes.

## 0. Prepare

```bash
# Ensure DB is up, schema migrated, demo data loaded
docker compose up -d database
nix-shell --run "php bin/console doctrine:migrations:migrate --no-interaction"
nix-shell --run "composer seed"

# Start dev server
nix-shell --run "php -S 127.0.0.1:8123 -t public public/index.php"
```

Open <http://127.0.0.1:8123/> in your browser. **Use an incognito window** —
keeps cookies from earlier sessions out of the way and lets you re-test cleanly.

Open DevTools (Cmd+Option+I). Keep the **Console** and **Network** tabs visible
— a red error in Console while clicking through is an automatic fail.

---

## 1. Public surface

- [ ] **GET /** — Home page renders. Bootstrap nav at top with `Domů` /
      `Návod` items. Title `🏋️ TrainLog`. No JS errors in console.
- [ ] **GET /navod** (works anonymously) — Návod page renders with the
      table-of-contents sidebar on the left. Click a TOC link, page jumps to
      that section anchor.
- [ ] **GET /register** — Form has `E-mail`, `Jméno`, two password fields.
- [ ] **Register a throwaway user**: `test1@example.com` / `Heslo123!` / name `Test`.
      Should auto-login and redirect to `/`. Navbar now shows the user name + a
      `Odhlásit` button. Logged-in nav adds `Cviky / Tréninky / Šablony`.
- [ ] **Click `Odhlásit`** — redirects to `/`, navbar shows `Přihlášení` again.

## 2. Login as the demo user

- [ ] **GET /login** — form visible. Try wrong password — flash error in red.
- [ ] **Log in as `demo@trainlog.local` / `demo`** — redirect to `/`. Navbar
      shows `Demo uživatel` + nav items `Cviky / Tréninky / Šablony / Návod`.

### 2a. Home dashboard (logged in)

- [ ] **GET /** — page now greets `Vítejte zpět, Demo uživatel.` and shows
      `Posledních 5 tréninků` as a Bootstrap list-group. Each row is clickable
      (whole row links to `/workouts/{id}`) and shows name, date, set count
      badge, total volume in kg.
- [ ] **Below the list**: `+ Nový trénink` primary button + `Zobrazit celou
      historii` outline button.
- [ ] If a user has **no** workouts yet, the same page shows an `alert-info`
      empty state pointing at `/workouts/new` and `/templates`. (To test:
      register a fresh user — see §2b.)

### 2b. First-user-becomes-admin (skip if you're not on a fresh DB)

This only triggers when the `user` table is empty. On a seeded DB the demo
admin already exists; to verify, drop everything first:

```bash
nix-shell --run "php bin/console doctrine:database:drop --force --if-exists"
nix-shell --run "php bin/console doctrine:database:create"
nix-shell --run "php bin/console doctrine:migrations:migrate --no-interaction"
nix-shell --run "php bin/console doctrine:fixtures:load --group=default --no-interaction"
# ^ loads ONLY muscle groups, not demo users/workouts
```

- [ ] **Register `boss@example.com` / `Heslo123!` / Boss`** via `/register`.
- [ ] A blue **flash message** appears: `Jste prvním uživatelem instance —
      automaticky jste získali roli administrátora.`
- [ ] Navbar immediately shows `Administrace` item — this account is admin
      without any CLI / DB step.
- [ ] **Register a second user** `second@example.com` — no flash message,
      navbar has **no** `Administrace` item. They're a regular user.

After this test, restore the demo seed for the rest of the checklist:

```bash
nix-shell --run "composer seed"
```

## 3. Exercise CRUD + filter

- [ ] **GET /exercises** — table shows the 10 seeded public exercises owned by
      the admin. Each row has muscle-group badges.
- [ ] **Filter by `prsa` only** — check the `prsa` box, click `Filtrovat`. Table
      should narrow to exercises with `prsa` tag (`Benchpress`, `Kliky na
      bradlech` at minimum). URL has `?muscle_group[]=...`.
- [ ] **Click `Zrušit filtr`** — returns to full list.
- [ ] **Click `+ Nový cvik`** — form renders.
- [ ] **Create `Moje izolace bicepsu`** with description, check `biceps` muscle
      group, leave `Veřejný` unchecked (disabled for non-admins anyway).
- [ ] **Click `Vytvořit`** — redirect to detail page. Owner shows `já`.
      Visibility badge is `soukromý`.
- [ ] **Click `Upravit`** — change description, save. Confirm new value sticks.
- [ ] **Click `Smazat`** — confirm dialog → exercise gone from `/exercises`.
- [ ] **GET /exercises/{id}/show** for a seeded exercise (e.g. Benchpress) —
      `Vývoj` button visible, recent sets table populated.

## 4. Workout CRUD + dynamic set rows

- [ ] **GET /workouts** — list of 18 seeded workouts, newest first. Each row
      shows date, name, count of distinct exercises, total sets, volume in kg.
- [ ] **Click `+ Nový trénink`** — form opens, no sets visible yet.
- [ ] **Click `+ Přidat sadu` three times** — three rows appear. Each is a row
      of selects/inputs for: cvik / opak. / váha (kg) / RPE / `Odebrat sadu`.
- [ ] **Click `Odebrat sadu` on the middle row** — only first + last remain.
- [ ] **Fill in both rows**: cvik=`Benchpress`, reps=5, weight=80, RPE=8 (first)
      and reps=5, weight=82.5, RPE=8.5 (second).
- [ ] **Save** — redirect to detail page. Both sets present, `position` numbered
      `1, 2` (re-numbered server-side). Per-exercise summary card shows
      `2 sad · max 82.5 kg · top sada 5×82.50 · objem 812 kg`. Total volume
      footer matches.
- [ ] **Edit the workout** — change first set to 6 reps, save. Volume updates.
- [ ] **Delete the workout** — confirm dialog → gone from `/workouts`.

## 5. Templates

- [ ] **GET /templates** — empty state (demo seed makes no templates) with CTA.
- [ ] **Click `+ Nová šablona`** — same form layout as workout. Add 2 sets
      (e.g. `Dřep` × 5 @ 80kg, `Mrtvý tah` × 3 @ 100kg). Save as `Demo PPL`.
- [ ] **Back at `/templates`** — card visible with set count + exercise names.
- [ ] **Click `Spustit trénink`** — redirect to `/workouts/{newId}/edit` with
      sets pre-filled at the same weights/reps. Today's date set.
- [ ] **Save** — workout appears at top of `/workouts` (templates excluded
      from history, so check the `Demo PPL` template is NOT in the list, but
      the instantiated workout IS).

## 6. Progress chart (Chart.js)

- [ ] **GET /exercises/{Benchpress id}/progress** — page renders chart.
- [ ] **Chart shows ~6 data points** (one per workout date over 6 weeks) on
      the `Max váha` metric by default. Y-axis starts at 0. Line is filled
      light blue.
- [ ] **Click `Objem`** — chart re-renders, y-axis scale jumps higher.
- [ ] **Click `Odhad 1RM`** — chart re-renders again with Epley-estimated 1RM.
- [ ] **Network tab**: confirm exactly one XHR to `/api/exercises/{id}/progress.json`
      returning a JSON object with `labels`, `maxWeight`, `volume`, `estimated1rm`
      arrays. **No PII** in the response.

## 7. Personal record badges

- [ ] **GET /workouts/{any seeded workout}** — at least one set should have
      🏆 PR badges (because each week was the heaviest yet for that exercise).
- [ ] **GET /exercises/{Benchpress id}** — recent sets table has a `PR` column
      with badges on the all-time best.
- [ ] Verify all three PR kinds appear somewhere: `🏆 PR — váha`,
      `🏆 PR — objem`, `🏆 PR — 1RM`. The most-recent (week 0) workout is the
      easiest place to find them.

## 8. PDF export

- [ ] **GET /workouts/{any id}** → click `PDF` button. Downloads
      `workout-{id}.pdf`. **Open it** — Czech diacritics render correctly
      (DejaVu Sans). Per-exercise tables present. Footer says
      `TrainLog · vygenerováno YYYY-MM-DD HH:MM`.
- [ ] **GET /workouts/export/2026-05.pdf** (or whatever month has data) —
      downloads monthly summary. Has totals card, table of workouts, summary
      per cvik.
- [ ] **Try /workouts/export/2025-01.pdf** — month with no data. PDF still
      renders, shows `Žádné tréninky v tomto měsíci.`

## 9. Admin (log out, log back in as admin)

```
admin@trainlog.local / admin
```

- [ ] **GET /admin** — 3-link dashboard.
- [ ] **GET /admin/users** — 3 users listed. Try `Povýšit` on a regular user
      (e.g. `hosta@trainlog.local`) — role badge flips. Try again to demote.
- [ ] Verify you **cannot** delete or change your own role (`(vy)` shown
      instead of buttons).
- [ ] **GET /admin/exercises** — 10 public exercises. Each row has buttons
      `Upravit / Skrýt|Zveřejnit / Smazat`.
- [ ] **Click `Upravit`** on any exercise — opens the user-facing edit form.
      As admin, the `Veřejný` checkbox is **enabled** (not disabled like for
      regular users). Tweak the description and save — flash success.
- [ ] **Click `Skrýt`** on a public exercise — it becomes `soukromý`, the
      `Smazat` button disappears (only public can be admin-deleted).
- [ ] Re-publish it via `Zveřejnit`.
- [ ] **GET /admin/muscle-groups** — 11 groups. Click `+ Nová partie`, create
      `test partie`, save. Verify it appears in the table with `0` cviků.
      Click `Smazat` — confirm → gone. Try to delete `prsa` — button is
      **disabled** because the count > 0.

## 10. Access control

Open a **second incognito window** (or use the `hosta@trainlog.local` / `demo`
account):

- [ ] As `hosta`, **GET /exercises/{id of Moje izolace bicepsu}** if it still
      exists — should be `403` because it's `soukromý` and not yours.
      (If you deleted it in step 3, skip this.)
- [ ] As `hosta`, **GET /workouts/{any of demo user's workout ids}** → 403.
- [ ] As `hosta`, **GET /admin** → redirect to `/login` (or 403 if already
      logged in) — `hosta` is not admin.

## 11. In-app user guide (`/navod`)

- [ ] **GET /navod** (logged in OR anonymous — works either way). Page
      renders with a sticky TOC on the left (hidden on phones) and 9
      numbered sections on the right: Úvod, Registrace a přihlášení,
      Cviky, Tréninky a série, Šablony, Graf vývoje, PR, Export do PDF,
      Administrace, FAQ.
- [ ] **Click each TOC link** — page scrolls to the section anchor
      (`#uvod`, `#registrace`, etc.).
- [ ] **Inline links** inside the guide (e.g. `/admin/users`, `/exercises`)
      work and are not broken. Hover one to verify the URL in the status bar.
- [ ] Navbar `Návod` item is **highlighted** (active state) while on `/navod`.

## 12. JS / asset health (open while clicking around)

- [ ] DevTools **Console** — no `404` on `/assets/vendor/...`. No JS exceptions
      from `chart_controller`, `collection_controller`, `bootstrap`, or
      `csrf_protection_controller`.
- [ ] DevTools **Network** — `/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js`,
      `/assets/vendor/chart.js/...`, `/assets/vendor/@popperjs/...` all return
      200, served by AssetMapper.
- [ ] Bootstrap CSS comes from `cdn.jsdelivr.net` (not AssetMapper).

## 13. Optional: wger import

Only if you have internet and want to exercise the optional task 014.

```bash
nix-shell --run "php bin/console app:exercise:import-wger --limit=10 --language=en"
```

- [ ] Command finishes with `[OK] Imported N new exercises (skipped 0 duplicates).`
- [ ] **GET /admin/exercises** — new rows appear, owner is `Demo admin`,
      `veřejný` badge set.
- [ ] Re-run the command — should now say `Imported 0 new exercises
      (skipped N duplicates).` (idempotency).

---

## Pass criteria

Every checkbox in §1–§12 ticked, no red errors in DevTools console at any
point. §2b only applies on a fresh DB. §13 is gravy.

## If something fails

1. Screenshot the failing step + the Console / Network panel.
2. Note the exact URL and what you clicked.
3. File against the relevant task in `.tasks/` or open a new follow-up.

## Cleanup

```bash
# Stop the dev server (Ctrl+C in its terminal)
pkill -f "php -S 127.0.0.1:8123"

# Optionally reset DB to a clean demo state for the next run
nix-shell --run "composer seed"
```
