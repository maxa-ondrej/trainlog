# 004 — Frontend: Bootstrap 5 + Stimulus + Chart.js

Wire client-side dependencies through Symfony AssetMapper (no Node toolchain
in production).

## Acceptance criteria

- [ ] `php bin/console importmap:require bootstrap @popperjs/core chart.js`
      adds the entries to `importmap.php`.
- [ ] `assets/styles/app.css` imports Bootstrap 5 (either via CDN link in
      `base.html.twig` or via `importmap:require bootstrap/dist/css/bootstrap.min.css`
      — pick CDN to keep AssetMapper light).
- [ ] `assets/app.js` imports `bootstrap` so dropdowns/modals work.
- [ ] A working Stimulus controller (`assets/controllers/hello_controller.js`
      already exists from skeleton — verify it boots).
- [ ] `templates/base.html.twig` renders a Bootstrap navbar with branding,
      links: Domů / Cviky / Tréninky / Přihlášení / Registrace.

## Touched files

- `importmap.php`
- `assets/app.js`
- `assets/styles/app.css`
- `templates/base.html.twig`
- `templates/_navbar.html.twig`

## Depends on

- 002 (navbar shows login state).
