# Changelog

All notable changes to this project are documented in this file. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project uses [Semantic Versioning](https://semver.org/).

## [0.1.6] - 2026-09-18

### Fixed

- **October CMS Marketplace rejected the registered plugin code**:
  *"Supplied plugin code 'AmjadIqbal' does not match author code of
  'Amjad'"*. The Marketplace author code (assigned at registration, "cannot
  be changed after you register" per October's own docs) turned out to be
  `Amjad`, not `AmjadIqbal` as this plugin's namespace assumed throughout.
  Renamed the plugin's namespace root from `AmjadIqbal\VueForge` to
  `Amjad\VueForge` everywhere: `Plugin.php`, `classes/ViteResolver.php`,
  `formwidgets/VueWidget.php`, `console/MakeVueWidget.php`,
  `tests/VueWidgetTest.php`, `composer.json` (package name
  `amjadiqbal/vueforge-plugin` → `amjad/vueforge-plugin`, PSR-4 autoload
  key), install-path strings in `vite.config.ts` and
  `.github/workflows/tests.yml`, and `README.md`. The plugin now installs
  at `plugins/amjad/vueforge` (was `plugins/amjadiqbal/vueforge`).
  **The GitHub org/username (`amjadiqbal`) and the display author name
  ("Amjad Iqbal" in `pluginDetails()`) are unaffected** - only the
  Marketplace's own internal author-code namespace changed, which is a
  separate concept from either of those. Verified locally (typecheck, test,
  build all pass) before pushing.

### Changed

- Renamed the GitHub repo from `oc-vueforge` to `oc-vueforge-plugin`,
  matching October CMS's Developer Guide repository-naming convention
  ("Repository Naming": plugins should use a `-plugin` suffix with an
  optional `oc-` prefix). Confirmed against 6 real live plugins via the
  Packagist API (`rainlab/blog-plugin`, `rainlab/builder-plugin`,
  `offline/oc-mall-plugin`, `offline/oc-site-search-plugin`,
  `offline/oc-gdpr-plugin`, `initbiz/seostorm-plugin`) - every one has its
  GitHub repo name exactly matching its Composer package name, no
  exceptions found. Updated `Plugin.php`'s `homepage` and `README.md`'s CI
  badge links to match. GitHub auto-redirects the old URL; the Packagist
  webhook survived the rename intact (confirmed via the GitHub API).

## [0.1.4] - 2026-09-18

### Fixed

- **Composer package name was rejected by the October CMS Marketplace**:
  `amjadiqbal/vueforge` → the submission form returned "expected value is
  `amjadiqbal/vueforge-plugin`". Confirmed against October CMS's own
  Publishing Packages docs: the package name **must** end in `-plugin`.
  Fixed in `composer.json`. The installed directory name is unaffected -
  `extra.installer-name` was already correctly set to `vueforge`, so the
  plugin still installs at `plugins/amjadiqbal/vueforge` regardless of the
  Composer package name.
- Added `"october/rain": ">=4.2"` to `composer.json`'s `require` - the
  documented minimum platform version was stated in the README but never
  actually enforced at the Composer level.

### Changed

- Regenerated the marketplace banner and icon at the **actual required
  dimensions**, confirmed from October CMS's Quality Guidelines
  (previously guessed and wrong): icon 64x64 transparent PNG (was a 512x512
  SVG), banner 837x348 PNG (was 1280x640 SVG, and re-composed rather than
  stretched to avoid distorting the aspect ratio). New files:
  `../../design/vueforge-icon-64.png`, `../../design/vueforge-banner-837x348.png`.
- Rewrote `listing/MARKETPLACE_CHECKLIST.md` and `../../design/ASSET_PROMPTS.md`
  against the real, verified October CMS documentation (Developer Guide,
  Publishing Packages, Quality Guidelines) rather than assumptions -
  package/repo naming rules, exact asset dimensions, screenshot composition
  rules, and description-field content rules (no HTML, Content vs
  Documentation field split).

## [0.1.3] - 2026-09-18

### Fixed

- **CI was failing on `npm test`** (`TypeError: webidl.util.markAsUncloneable is not a function`).
  Root cause: `jsdom@30` requires Node `^22.22.2 || ^24.15.0 || >=26.0.0`, but this project targets
  Node 18+ (its own stated requirement) and CI runs Node 20 - jsdom 30 simply doesn't run there.
  Downgraded `jsdom` to `^25.0.0` (supports Node >=18, matching the stated requirement) rather than
  raising the Node requirement for everyone installing this plugin. Verified locally: typecheck,
  test, and build all pass.

### Changed

- **Removed internal process/tracking files from the repository** - `PROJECT_PROGRESS.md`,
  `docs/MARKETPLACE_CHECKLIST.md`, and `docs/ASSET_PROMPTS.md` were build logs and internal
  checklists, not documentation of how to use the plugin, and don't belong in a public repo.
  Moved to this product's local workspace folders (outside `src/`, not published) instead.
  `docs/VUE3_MIGRATION_GUIDE.md` stays - it's genuine usage documentation.

## [0.1.2] - 2026-09-18

### Added

- `art/banner.svg` and `art/icon.svg` — committed copies of the marketplace banner/icon (source of
  truth in `../design/` at the channel level, outside this repo) for display in this README.
- README hero banner, icon-in-title, and CI/license/version badges.

## [0.1.1] - 2026-09-18

### Changed

- **Restructured the repository so the plugin sits at the repo root**,
  resolving the Packagist composer.json-root conflict flagged in `0.1.0`.
  `Plugin.php`, `assets/`, `classes/`, `console/`, `formwidgets/`, `tests/`,
  `composer.json`, `package.json`, etc. moved out of
  `plugins/amjadiqbal/vueforge/` up to the repo root, matching how official
  October plugins ship on Packagist (a plugin's repo root *is* the plugin).
  No functional/runtime code changed - `.gitignore`, CI, and the README's
  Installation section were updated to match.

Still `0.x`, not `1.0.0`, for the same reason as `0.1.0` below: no real
browser HTTP login/save round-trip has been verified yet, and this hasn't
been published to Packagist or the October Marketplace.

## [0.1.0] - 2026-09-17

Initial build. Versioned `0.1.0` rather than `1.0.0` deliberately: this
plugin has real, working PHPUnit + Vitest coverage against a live October CMS
install and passes it, but has **not yet** been exercised through a real
backend HTTP login/save round-trip in a browser, has not been published to
Packagist/the October Marketplace, and its Composer-package-root-vs-repo-root
layout still needs resolving before Packagist submission (see
`docs/MARKETPLACE_CHECKLIST.md`). `1.0.0` is reserved for after those close.

### Added

- `formwidgets/VueWidget.php` - `FormWidgetBase` subclass hydrating an
  arbitrary Vue 3 SFC, with JSON prop serialization and sanitizing
  `getSaveValue()`.
- `classes/ViteResolver.php` - dev-server vs. production `manifest.json`
  asset resolution.
- `assets/js/vueforge.ts` - ESM hydrator with dynamic component loading,
  re-hydration on `ajax:update-complete`/`page:updated`.
- `assets/js/composables/useVueForge.ts` - bi-directional v-model <-> hidden
  `<input>` sync.
- `assets/js/composables/useOctoberAjax.ts` - typed wrapper around October's
  `window.jax` (Larajax) AJAX API.
- `assets/vue/components/TagInput.vue` and `JsonEditor.vue` - example
  components (array state and nested object state respectively).
- `console/MakeVueWidget.php` - `php artisan vueforge:make {plugin} {name}`
  generator.
- PHPUnit suite (`tests/VueWidgetTest.php`, 14 tests) run against a real
  October CMS 4.4.5 install.
- Vitest + `@vue/test-utils` suite (`tests/js/vueforge.test.ts`, 5 tests)
  covering mount/interact/unmount for both example components.
- `README.md`, `docs/VUE3_MIGRATION_GUIDE.md`, `docs/ASSET_PROMPTS.md`,
  `docs/MARKETPLACE_CHECKLIST.md`.
- `.github/workflows/tests.yml` running both suites on PRs.

### Fixed (found during this initial build, not pre-existing regressions)

- `VueWidget::encodeProps()`/`encodeValue()`: PHP's `JSON_HEX_QUOT` flag does
  not escape JSON's own structural quotes, so JSON embedded directly into a
  double-quoted HTML attribute broke the attribute (and was an XSS vector)
  whenever a prop value contained a double quote. Fixed with an explicit
  `htmlspecialchars(..., ENT_QUOTES)` pass.
- `JsonEditor.vue`: a two-way `watch()` between its `rows` and `data` refs
  caused an infinite reactive update loop ("Maximum recursive updates
  exceeded"), caught by the Vitest mount test. Fixed by making `rows` the
  single source of truth (one-directional sync into `data`).
- `VueWidget::getSaveValue()`: the literal JSON string `"null"` is valid JSON
  decoding to PHP `null`, which is a legitimate value but not a usable
  payload for any VueForge component - now normalized to the same safe
  default as malformed JSON, rather than persisting a bare `null`.
