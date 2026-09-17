# VueForge - build progress log

Built 2026-09-17, autonomously, phase by phase, per the spec in
`../CLAUDE.md` (`marketplaces/octobercms/CLAUDE.md`). This file is the
handoff record: what was done, what was genuinely verified (and how), what
deviated from the original spec and why, and what's left for a human.

## Phase 1 - Scaffold

Verified the pre-existing directory structure, wrote
`plugins/amjadiqbal/vueforge/composer.json` (PSR-4 `AmjadIqbal\VueForge\` ->
plugin root, `type: october-plugin`), `package.json` (Vue 3.4, TypeScript,
Vite 5, `@types/node`), `tsconfig.json` (strict mode on), and
`vite.config.ts` (multi-entry library build: the hydrator plus each example
component gets its own manifest key, so `ViteResolver` can load only what a
given widget needs).

## Phase 2 - Backend core

`formwidgets/VueWidget.php` and `classes/ViteResolver.php`, written against
October's **real** `Backend\Classes\FormWidgetBase` / `WidgetBase` /
`AssetMaker` source (composer-installed in the throwaway test app, not
guessed) - confirmed method signatures for `getSaveValue()`, `getLoadValue()`,
`getFieldName()`, `addJs()`/`addCss()`'s flat (not nested) attributes array,
and `getAssetPath()`'s pass-through behavior for absolute URLs, before
writing any of this plugin's code.

**Deviation, found and fixed via testing, not by inspection:** the original
`encodeProps()`/`encodeValue()` relied on PHP's `JSON_HEX_QUOT` flag to make
JSON safe inside an HTML attribute. That flag only escapes quotes *inside*
JSON string values, not JSON's own structural quotes - confirmed by directly
testing `json_encode(['a'=>'b'], JSON_HEX_QUOT)`, which returns `{"a":"b"}`
unchanged. A real widget render therefore produced broken/exploitable HTML.
Fixed with an explicit `htmlspecialchars(..., ENT_QUOTES)` pass over the
JSON string; the browser's `Element.getAttribute()` reverses the HTML-entity
encoding automatically before `JSON.parse()` on the frontend.

## Phase 3 - Frontend ESM hydration

`assets/js/vueforge.ts`, `useVueForge.ts`, `useOctoberAjax.ts`. Corrected two
assumptions during research against October's actual shipped JS (not
invented):

- October's v4.2+ AJAX framework is **Larajax**, exposed globally as
  `window.jax` (confirmed via `vendor/larajax/larajax/resources/types/
  index.d.ts`), not `oc.ajax`/jQuery `.request()`. `useOctoberAjax()` wraps
  `jax.ajax()` directly.
- Backend partial/AJAX updates fire `ajax:update-complete` / `page:updated`
  (confirmed via the same type declarations), not the jQuery-era
  `ajaxUpdate`/`ajaxComplete` events I initially assumed.
- October's own mandatory `$componentName` (confirmed real, via
  `modules/system/classes/VueComponentBase.php`) is a **PHP property** on
  October's own native `VueComponentBase` system, not a JS instance
  property. VueForge doesn't register through that system at all, so it
  doesn't apply here - the first draft of `vueforge.ts` incorrectly set
  `app.config.globalProperties.$componentName`, implying compliance with a
  mechanism that isn't actually relevant. Removed and replaced with an
  honestly-named `$vueforgeComponent` debug property, with the distinction
  documented in `docs/VUE3_MIGRATION_GUIDE.md`.

## Phase 4 - Artisan generator

`console/MakeVueWidget.php`, `vueforge:make {plugin} {name}`. Verified for
real against a second, freshly-created test plugin
(`TestVendor.WidgetTest`) in the throwaway app: generated PHP passed `php -l`,
refused to overwrite without `--force`, and printed a correct YAML usage
hint. Noted (and warned about, in the command's own output) that the
generated component's `useVueForge` import uses a relative path across
plugin folders, which only resolves in a consuming plugin's own Vite dev
server if `server.fs.allow` includes VueForge's directory - a real Vite
constraint, not solved here (would require either a published npm package or
a documented dev-server config change; left as a documented limitation, see
`docs/VUE3_MIGRATION_GUIDE.md` and the README's Artisan CLI section).

## Phase 5 - Example components + integration verification

`TagInput.vue` (array state) and `JsonEditor.vue` (nested object state).

**Genuinely verified**, in order of rigor:

1. **Real October FormWidget render**, via `VueWidget::render()` called
   directly against a real `Backend\Classes\FormField` and a real Eloquent
   model, going through October's actual `ViewMaker`/`AssetMaker` trait
   chain (not a stub). This is where the `checkBaseDir()` gotcha (see Phase
   2 / migration guide item 7) was found: the plugin must be physically
   inside the test app's `plugins/` directory, not symlinked from outside
   `base_path()`, or `makePartial()` silently returns an empty string.
   Switched the throwaway app's link from a symlink to an `rsync` copy once
   this was understood, and confirmed correct HTML output afterward.
2. **14 PHPUnit tests** (`tests/VueWidgetTest.php`) against that same real
   `FormField`/`VueWidget` pairing - prop serialization, JSON parsing,
   sanitization against objects/resources/malformed JSON, disabled-field
   behavior. All passing as of the final build.
3. **5 Vitest + `@vue/test-utils` (jsdom) tests**
   (`tests/js/vueforge.test.ts`) - real mount, user interaction (typing a
   tag, editing a JSON row), hidden-input synchronization, and unmount, with
   `console.error` spied on to catch silent failures. This is where the
   `JsonEditor.vue` infinite-loop bug (Phase 5/6) was actually caught.

**Not verified**: a real backend HTTP login + full page render + native form
submit, in an actual browser, against a FormController wired up in the
throwaway app. No headless-browser tool (Playwright/Puppeteer/etc.) was
available in this environment, and building a full FormController +
Twig/PHP backend view + authenticated HTTP client from scratch within the
remaining scope of this session was judged lower-value than the direct
PHPUnit/Vitest verification above, which exercises the same PHP and Vue code
paths a browser session would, just not the surrounding HTTP/session/CSRF
plumbing. This is the single biggest thing a human should do before treating
this plugin as production-ready - see "Next steps" below.

## Phase 6 - Testing

- PHPUnit: 14/14 passing, against a real October CMS 4.4.5 / october/rain
  ^4.4 install (Composer `create-project october/october`).
- `vue-tsc --noEmit`: clean.
- `npm run build` (Vite production build): succeeds, produces a
  `manifest.json` whose keys match what `ViteResolver.php` expects (verified
  by inspecting the actual generated manifest, not assumed).
- "No console errors/leaks across mount/unmount": verified via the Vitest
  suite (`console.error` spy), per the spec's own stated fallback for when a
  real browser session isn't available. Caught the `JsonEditor.vue` infinite
  reactive loop mentioned above.

## Phase 7 - Docs

`README.md` placed at `plugins/amjadiqbal/vueforge/README.md` (the Composer
package root, matching where `composer.json` lives and what Packagist reads
by convention), not the git repo root - the repo root has no README of its
own since the whole repo's payload is that one plugin directory.

`docs/VUE3_MIGRATION_GUIDE.md` written from real source inspection (October's
actual `VueComponentBase`, `VueMaker` trait, `vue-application.js`'s `mitt()`
usage, and Larajax's real type declarations) rather than generic Vue 2 -> 3
advice - see the file itself for the full list of what was confirmed and
where.

`docs/ASSET_PROMPTS.md` and `docs/MARKETPLACE_CHECKLIST.md` per spec, both
noting that final graphics belong in `../design/`, one level above `src/`.

## Phase 8 - Git/release

- `.gitignore`: excludes `node_modules/`, `vendor/`, Vite's `assets/dist/`
  and `.vite/` caches, editor/OS cruft. **Decision**: built `assets/dist/` is
  NOT committed - documented in the README's own "Why assets/dist/ is not
  committed" section. Reasoning: it's fully reproducible via `npm run
  build`, and committing machine-generated hashed filenames that can differ
  across Node/Vite patch versions is more likely to cause confusing diffs
  than to help anyone.
- `.github/workflows/tests.yml`: runs both the TypeScript suite (typecheck +
  Vitest + build) and the PHPUnit suite. The PHPUnit job installs a
  throwaway October CMS app in CI (mirroring exactly what was done manually
  during this session) since a plugin's tests need October's real base
  classes to mean anything - this is unusual for a "simple" plugin CI setup
  but is the only way to keep the PHPUnit suite honest going forward.
- `CHANGELOG.md`: versioned **0.1.0**, not 1.0.0 - see the changelog's own
  entry for the reasoning (no real browser E2E yet, not yet published
  anywhere).
- `LICENSE`: MIT, "Amjad Iqbal".
- `updates/version.yaml`: added (was missing from the initial scaffold) so
  October's own plugin version tracking (`php artisan plugin:list`) actually
  recognizes the plugin - confirmed working in the throwaway app.
- Initial commit made locally. **No remote added, nothing pushed** - per
  instructions, `gh repo create` and any push require the user's explicit
  go-ahead.

## Next steps (for the user, not doable in this session)

1. ~~**Create the GitHub repo** `amjadiqbal/oc-vueforge` and push.~~ Done
   2026-09-17 - public, MIT, `v0.1.0` tagged and released.
2. **Real browser verification** (the one deferred item above): stand up a
   FormController in a real (or the throwaway) October app, log in, and
   confirm the widget round-trips through an actual HTTP save - not
   strictly required to publish, but the honest gap in this session's
   verification.
3. ~~**Resolve the Packagist composer.json-root issue**~~ Done 2026-09-18 -
   see "Phase 8 addendum" below.
4. **Produce the banner/icon/screenshots** per `docs/ASSET_PROMPTS.md`, into
   `../design/` (not this repo).
5. **Register at octobercms.com** and submit per
   `docs/MARKETPLACE_CHECKLIST.md`.
6. Decide the actual `v1.0.0` cutover point per `CHANGELOG.md`'s reasoning
   (after step 2, at minimum).

## Phase 8 addendum - repo restructure (2026-09-18)

Resolved the Packagist composer.json-root conflict flagged in Phase 8: the
entire plugin (`Plugin.php`, `assets/`, `classes/`, `console/`,
`formwidgets/`, `tests/`, `composer.json`, `package.json`, etc.) was moved
from `plugins/amjadiqbal/vueforge/*` up to the repository root, matching how
official October plugins actually ship on Packagist (a plugin's GitHub repo
root *is* the plugin - `composer/installers`' `october-plugin` type derives
the install path `plugins/<vendor>/<name>/` from the package's Composer
`name`, not from any nested folder in the source repo, so no
`installer-name`/path mapping was needed). Updated to match: `.gitignore`
(`assets/dist/` path), `.github/workflows/tests.yml` (working directories,
the rsync step that installs this plugin into the CI throwaway app), and
`README.md`'s Installation section. Internal references to
`plugins/amjadiqbal/vueforge/...` inside PHP docblocks, `vite.config.ts`'s
`base` URL, and `MakeVueWidget.php`'s generated-file comments were
deliberately left unchanged - those describe the plugin's *installed*
location inside a consuming October app, which is unaffected by this repo's
own source layout.
