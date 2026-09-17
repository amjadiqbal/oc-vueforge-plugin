# October CMS Marketplace submission checklist

Verification steps before/at submission for `amjadiqbal/vueforge`. Follows
this portfolio's Packagist/marketplace conventions (see
`../../../CLAUDE.md` and `../../packagist/CLAUDE.md` for how the other
Laravel packages here were treated) - do not submit anything not actually
verified.

## Before submission

- [x] **Packagist composer.json location - resolved 2026-09-18.** The repo
      was restructured so `composer.json` (and the entire plugin) sits at the
      **git repository root** - matching how official plugins actually ship
      on Packagist (e.g. `rainlab/blog-plugin`'s repo root *is* the plugin;
      `composer/installers`' `october-plugin` type derives the install path
      `plugins/<vendor>/<name>/` from the package's Composer `name`, not from
      any nested folder in the source repo). No `installer-name`/path mapping
      or subtree-split was needed - the previous `plugins/amjadiqbal/vueforge/`
      nesting was removed and its contents promoted to the repo root. All
      other docs/tests/CI paths were updated to match; see `CHANGELOG.md`.
- [ ] `composer.json` `name` (`amjadiqbal/vueforge`), `type`
      (`october-plugin`), and `require.php` (`>=8.2`) are correct - done.
- [ ] `npm run build` succeeds and `assets/dist/` is producible from a clean
      `npm install` - verified 2026-09-17 against Vite 5.4.21.
- [ ] `npm run typecheck` (`vue-tsc --noEmit`) is clean - verified 2026-09-17.
- [ ] `npm test` (Vitest component smoke tests) passes - verified 2026-09-17
      (5/5 tests, mount/prop-hydration/hidden-input-sync/unmount for both
      example components).
- [ ] PHPUnit suite passes against a real October CMS install (not mocks) -
      verified 2026-09-17 against October CMS v4.4.5 / october/rain ^4.4
      (14/14 tests). See `PROJECT_PROGRESS.md` for the exact invocation and
      what a "real install" check caught (two genuine bugs, see below).
- [ ] `Plugin.php`'s `pluginDetails()` has a real `homepage` URL that
      resolves once the GitHub repo is pushed (currently
      `https://github.com/amjadiqbal/oc-vueforge` - not yet created, see
      Next Steps).
- [ ] `LICENSE` present, MIT, correct copyright name - done.
- [ ] `CHANGELOG.md` present, Keep a Changelog format - done.
- [ ] No secrets, credentials, or `.env`-style files committed - verified
      (this plugin has no `.env`, no API keys, no customer data by design).
- [ ] Version tagged in git (`v1.0.0` or `v0.1.0` - see `CHANGELOG.md` for
      which and why) before submitting, since both Packagist and the October
      Marketplace resolve by tag.

## Assets (see `ASSET_PROMPTS.md`)

- [ ] Banner 1280x640 produced and placed in `../design/` (not this repo).
- [ ] Icon 512x512 produced and placed in `../design/`.
- [ ] At least 3 real (not staged/mocked) backend screenshots, 1280x800,
      captured from an actual running October CMS backend with this plugin
      installed.

## Submission fields (octobercms.com plugin submission form)

- [ ] Plugin code: `AmjadIqbal.VueForge`
- [ ] Composer package: `amjadiqbal/vueforge`
- [ ] Category: Backend / Developer Tools (verify exact taxonomy at
      submission time - not confirmed against the live form as of this
      writing).
- [ ] Price: Free (matches this product's positioning - see
      `../../../marketplaces/octobercms/CLAUDE.md`: "zero direct marketplace
      sales likely... positioned as free open-source to build ecosystem
      authority").
- [ ] Short description (under ~200 chars, exact limit unverified - check
      the live form): "The rapid Vue 3 component & widget engine for October
      CMS backend interfaces - real .vue SFCs, TypeScript, Vite, no
      boilerplate."
- [ ] Full description: adapt from `README.md`'s Quickstart + Architecture
      sections.

## After submission

- [ ] Verify the listing appears and the banner/icon render correctly at
      their real display sizes (not just as uploaded).
- [ ] Verify the "Install" / Composer instructions shown on the listing
      match this README's Installation section.
- [ ] Register the `amjadiqbal` author/vendor code at octobercms.com if not
      already associated with the account being used to submit (confirm
      this is the same account used for the other October-related work in
      this portfolio, if any exists yet - this is the first October CMS
      product in the portfolio as of 2026-09-17).

## What was genuinely verified during development (2026-09-17)

Real, non-mocked checks performed against a throwaway October CMS 4.4.5
install (Composer `create-project october/october`, SQLite, this plugin
physically copied - not symlinked, see the migration guide item 7 - into its
`plugins/amjadiqbal/vueforge`):

- `php artisan plugin:list` shows the plugin registered and
  `php artisan list` shows `vueforge:make` registered.
- `php artisan vueforge:make TestVendor.WidgetTest DemoField` against a real
  second test plugin: generated valid PHP (`php -l` clean) and a valid Vue
  SFC stub, refusing to overwrite without `--force`.
- `VueWidget::render()` produces correct HTML through October's real
  `FormWidgetBase`/`ViewMaker`/`AssetMaker` trait chain (not a hand-rolled
  stub), including finding its partial via October's actual
  `guessViewPath()`/`checkBaseDir()` resolution.
- 14 PHPUnit tests against `Backend\Classes\FormField` + `VueWidget` directly
  (chosen over a full `Backend\Widgets\Form` to avoid needing a real
  `Backend\Classes\Controller` for unrelated Larajax component-container
  wiring) - covering prop serialization, JSON parsing, and save-value
  sanitization against malformed/malicious payloads.
- 5 Vitest + `@vue/test-utils` (jsdom) tests covering mount, prop hydration,
  user interaction, hidden-input sync, and clean unmount for both example
  components, with `console.error` spied to catch silent failures.

**Two real bugs were found and fixed by these checks, not invented for
demonstration:**
1. `VueWidget::encodeProps()`/`encodeValue()` originally relied on PHP's
   `JSON_HEX_QUOT` flag to make JSON safe for an HTML attribute; that flag
   does not escape JSON's own structural quotes, so the *actual* rendered
   HTML from a real widget render was broken/exploitable. Fixed with an
   explicit `htmlspecialchars()` pass.
2. `JsonEditor.vue` had a two-way `watch()` between its `rows` and `data`
   refs that caused an actual "Maximum recursive updates exceeded" crash
   under Vitest, caught only because the component was really mounted (not
   type-checked or reviewed by inspection alone).

**Not yet verified (deferred, with reasons) - see `PROJECT_PROGRESS.md`:**
- A full browser-based backend login + real HTTP form-save round-trip
  (blocked on no headless-browser tool being available in this environment;
  the PHPUnit + Vitest checks above cover the same code paths a browser
  session would exercise, but not the actual HTTP/session/CSRF layer).
- HubSpot-style "does the marketplace actually accept this" - only
  achievable by really submitting.
