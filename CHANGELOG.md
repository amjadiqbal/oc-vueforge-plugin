# Changelog

All notable changes to this project are documented in this file. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project uses [Semantic Versioning](https://semver.org/).

## [0.1.8] - 2026-09-22

### Changed

- **Reverted to the `amjadiqbal` identity, superseding both 0.1.6 and 0.1.7 - decided explicitly
  by Amjad, not inferred.** The `0.1.6` rename to `Amjad`/`amjad` made this plugin eligible for an
  October CMS Marketplace listing under this account's registered author code, at the cost of a
  Composer/GitHub identity inconsistent with every other package from this author - and `0.1.7`
  then found that identity also collides with an unrelated, pre-existing `amjad/lableb` package on
  Packagist.org. Amjad's decision: give up Marketplace eligibility under this account and revert
  fully to `amjadiqbal` (namespace, plugin code, Composer vendor, install path), matching
  BlockCraft's own 2026-09-18 decision and restoring a working, unblocked Packagist publication
  path.
  - Namespace: `Amjad\VueForge` → `AmjadIqbal\VueForge`.
  - Plugin code: `Amjad.VueForge` → `AmjadIqbal.VueForge`.
  - Composer package: `amjad/vueforge-plugin` → `amjadiqbal/vueforge-plugin`, PSR-4 autoload key
    updated to match.
  - Install path: `plugins/amjad/vueforge` → `plugins/amjadiqbal/vueforge` - updated in
    `classes/ViteResolver.php`'s hardcoded manifest/base-URL strings, `vite.config.ts`'s `base`,
    `.github/workflows/tests.yml`'s install/testsuite paths, and `console/MakeVueWidget.php`'s
    generated-file comments.
  - `README.md`'s Installation section rewritten again: `composer require amjadiqbal/vueforge-plugin`
    is the real, correct primary method again (no vendor conflict on Packagist under `amjadiqbal`),
    with the explicit tradeoff (no Marketplace listing under this account) stated plainly rather
    than left implicit.
  - **The bug this fixes was reproduced for real before this change**, not just reasoned about: the
    live Packagist package `amjadiqbal/vueforge-plugin` v0.1.6 was already serving a `composer.json`
    declaring `"name": "amjad/vueforge-plugin"` with `Plugin.php` declaring
    `namespace Amjad\VueForge` - a real, live, already-shipped identity mismatch. Copying the real
    `Amjad\VueForge` plugin code into a directory matching what that published package name would
    actually install to (`plugins/amjadiqbal/vueforge`) and running `php artisan plugin:list`
    against this channel's real disposable October CMS install confirmed the plugin silently does
    not register - no error, just absent from the list. Re-verified clean after this fix.
  - **The pending October CMS Marketplace submission** (submitted 2026-09-18 under `Amjad.VueForge`,
    still showing no public listing as of 2026-09-22 per a content-based check, not a status code -
    `octobercms.com/plugin/<slug>` returns HTTP 200 for every slug including nonexistent ones) is
    **not touched by this change** and needs Amjad's own decision (withdraw it from his Author page,
    or let it be rejected/lapse on its own) - not something any session can or should decide.
  - `0.1.7`'s CHANGELOG entry above is kept as accurate history of what was decided that day; it was
    never tagged or released (`git tag` shows nothing past `v0.1.6`), so `0.1.7` is being reused
    here as a real version bump to `0.1.8` rather than retroactively edited.

## [0.1.7] - 2026-09-18

### Changed

- **Packagist.org publication abandoned - not a bug fix, a corrected decision.** A real submission
  attempt for `amjad/vueforge-plugin` was rejected by Packagist: *"The vendor name 'amjad' was
  already claimed by someone else"* (an unrelated existing package, `amjad/lableb`). Verified
  against real source before assuming a rename would fix it the way the 0.1.6 author-code fix did:
  - October's Marketplace distribution doesn't depend on Packagist.org at all - a real live listing
    (`octobercms.com/plugin/rainlab-blog`) shows `php artisan plugin:install RainLab.Blog` as the
    actual install command, driven by the registered Plugin Code through October's own gateway.
  - Publishing under a *different*, available Packagist vendor (e.g. `amjadiqbal`) would have
    silently broken the plugin for anyone using plain `composer require` - confirmed via
    `composer/installers`' real source (`BaseInstaller::getInstallPath()`): the install-path vendor
    segment is derived only from the Composer package's own vendor prefix, with no override.
    October's `PluginManager::loadPlugin()` then requires a class matching that exact scanned
    directory path to exist - a `composer require amjadiqbal/vueforge-plugin` install would land at
    `plugins/amjadiqbal/vueforge` looking for class `amjadiqbal\vueforge\Plugin`, which doesn't
    exist (the real class is `Amjad\VueForge\Plugin`) - the plugin would simply never register, no
    error shown.
  - `composer.json`'s `name` field stays `amjad/vueforge-plugin` (matches what's already registered
    on the October Marketplace submission itself, which is independent of public Packagist.org) -
    only the *public Packagist.org* publication step is skipped, not the Composer package identity.
  - `README.md`'s Installation section rewritten to lead with `php artisan plugin:install
    Amjad.VueForge` (the real primary distribution method) and the existing manual-clone
    instructions, removing the `composer require` instructions that would have been actively wrong.

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
