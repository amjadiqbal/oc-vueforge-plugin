# Changelog

All notable changes to this project are documented in this file. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project uses [Semantic Versioning](https://semver.org/).

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
  Installation section were updated to match. See `PROJECT_PROGRESS.md`'s
  "Phase 8 addendum" for the full reasoning.

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
