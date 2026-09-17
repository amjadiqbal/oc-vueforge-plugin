# October CMS v4.2+ Vue 3 / ESM backend: what changed, and where VueForge fits

This guide documents the real, source-verified changes in October CMS's backend
Vue tooling (confirmed against `october/rain` and the `modules/system`,
`modules/backend` sources of a v4.4.5 install, plus the `larajax/larajax`
package it ships with) and explains where VueForge intentionally diverges from
October's own native system.

## The two parallel systems

October CMS 4.2+ ships **two, unrelated** ways to put a Vue 3 component in the
backend. Confusing them is the single most common mistake when building a
custom widget.

1. **October's own native system** - `System\Classes\VueComponentBase` +
   the `VueMaker` trait (`registerVueComponent()`,
   `outputVueComponentTemplates()`). No SFCs, no TypeScript, no build step:
   components are plain JS objects (Options API) loaded as global ESM
   modules, with their template as a separate `.php` partial injected via a
   `<script type="text/template">` tag. This is what `php artisan
   make:vuecomponent` scaffolds.
2. **VueForge (this plugin)** - a `FormWidgetBase` subclass that hydrates a
   real Vue 3 `.vue` SFC (Composition API, `<script setup>`, TypeScript)
   compiled by Vite, for teams that want a real component-authoring
   experience and are willing to add a build step. VueForge does **not**
   register through `VueComponentBase`/`VueMaker` and is not a replacement
   for it - it solves a different problem (arbitrary FormWidget UIs, not
   backend page chrome).

Everything below is written from that vantage point: item 1 explains what
October itself requires you to know; items are marked **(native)** when they
only apply to October's own system, and **(VueForge)** when they describe how
this plugin bridges the gap.

## The 10 things that changed / that you need to know

1. **`$componentName` is mandatory - but it's a PHP property, not a JS one
   (native).** Every `VueComponentBase` subclass must declare `protected
   $componentName = 'vendor-plugin-name';` (kebab-case) or
   `getComponentName()` throws a `SystemException`. It's the tag name the
   component is registered under in `window.oc.vueComponents`.
   **(VueForge)** does not use this mechanism at all - a VueWidget's
   "component name" is just the PHP `$component` string used to look up an
   entry in `vueforge.ts`'s dynamic-import map, and has no relationship to
   October's own `$componentName`.

2. **Components are loaded as real ESM modules, then re-templated
   (native).** `outputVueComponentTemplates()` emits one `<script
   type="module">` block per page that dynamically imports each component's
   plain-JS file, then merges in the template string read from a
   `<script type="text/template">` block, and stores the merged definition
   on `window.oc.vueComponents[name]`. **(VueForge)** compiles real `.vue`
   SFCs with Vite instead, so the template is compiled into the component's
   render function at build time - no template-injection step needed.

3. **The AJAX framework is "Larajax", exposed as `window.jax`, not `oc.ajax`
   or jQuery `.request()` (native, applies to any backend JS).** Confirmed
   from `vendor/larajax/larajax/resources/types/index.d.ts`: the global is
   `jax.ajax(handler, options)`, returning a `Promise`. `useOctoberAjax()` in
   this plugin wraps exactly that.

4. **Backend partial/AJAX updates fire `ajax:update-complete` and Turbo page
   swaps fire `page:updated`, not jQuery's `ajaxUpdate`/`ajaxComplete`
   (native).** `vueforge.ts` listens for both to re-hydrate any new
   `[data-vueforge-widget]` mount points that appear after the initial page
   load (e.g. a Repeater row, a RecordFinder popup).

5. **A `mitt()`-based event bus replaces the old implicit Vue 2 event bus
   (native).** `modules/backend/assets/js/vueapp/vue-application.js`
   constructs `this.state.eventBus = Vue.markRaw(mitt())` inside the
   `VueApp` control (`data-control="vue-app"`) and shares it via Vue's
   reactive state - there is no more global `$root.$on`/`$emit`. If a
   VueForge component needs to talk to October's own native Vue components
   on the same page, it must do so through that same `mitt` bus instance
   (accessible via the `VueApp` control), not by inventing its own.

6. **Assets are resolved via Vite's `manifest.json`, keyed by source path,
   not by a fixed output filename (relevant to any Vite-built asset,
   including VueForge's).** Confirmed by building this plugin's own assets:
   the manifest's top-level keys are the *source* entry paths (e.g.
   `"assets/js/vueforge.ts"`), each mapping to a hashed `file` plus `css`/
   `imports` arrays. `classes/ViteResolver.php` reads exactly this
   structure to inject the right hashed `<script type="module">`/`<link>`
   tags, or the dev-server URL when `@vite/client` is reachable.

7. **`File::checkBaseDir()` restricts partial/view resolution to paths under
   the application's own `base_path()` by default (native, easy to trip
   over during plugin development).** `system.restrict_base_dir` defaults to
   `true`; a `FormWidgetBase::makePartial()` call silently returns an empty
   string (no exception) if the resolved partial file's real path - after
   symlink resolution - falls outside that base path. Symlinking a plugin
   into a test app's `plugins/` directory from elsewhere on disk will
   silently break rendering; a real (non-symlinked) install does not have
   this problem, since the plugin's files then genuinely live under
   `base_path()`.

8. **`useVueForge()`'s hidden-input bridge exists because Vue 3 components
   don't natively participate in a plain HTML form submit (VueForge).**
   October's backend `Form` widget still submits as a regular HTML form
   (via Larajax); nothing about Vue 3 changes that. `useVueForge()` keeps a
   `<input type="hidden">` in sync with the component's reactive state on
   every change so `VueWidget::getSaveValue()` receives a JSON string on
   submit exactly like any other form field.

9. **Values must be escaped for their HTML-attribute context, not just
   JSON-encoded (a real bug found and fixed while building this plugin).**
   PHP's `JSON_HEX_QUOT`/`JSON_HEX_APOS` flags only escape quote characters
   *inside* JSON string values - they do **not** escape JSON's own
   structural quotes. Embedding `json_encode($props, JSON_HEX_QUOT)`
   directly into a double-quoted `data-vueforge-props="..."` attribute
   breaks the attribute (and is an XSS vector) the moment a prop value
   itself contains user data. `VueWidget::encodeProps()`/`encodeValue()` run
   the JSON string through `htmlspecialchars(..., ENT_QUOTES)` afterward;
   `vueforge.ts`'s `el.getAttribute()` reverses the HTML-entity encoding
   automatically before `JSON.parse()`.

10. **A component-level `data`/`rows`-style two-way sync must pick one
    direction as the source of truth (a real bug found and fixed while
    building this plugin, general Vue 3 `watch()` gotcha, not October-
    specific).** `JsonEditor.vue` originally watched both `rows` (UI state)
    and `data` (the `useVueForge` value ref) and wrote into each other,
    which is a legitimate infinite-loop trap: assigning `data.value` inside
    the `rows` watcher scheduled the `data` watcher on the next reactive
    flush, whose callback then reassigned `rows`, forever. The fix is
    one-directional: `rows` is the only source of truth, folded into `data`
    on change; `data` is never watched back into `rows`.

## Practical takeaway

If you are building a small, form-scoped interactive control (a tag list, a
key/value editor, anything backed by a single field's JSON), use VueForge -
`vueforge:make` and the FormWidget/SFC path in this README are your on-ramp.
If you are building backend *page* UI that needs to interoperate with
October's own Vue components and their shared `mitt` event bus, use
October's native `VueComponentBase` (`php artisan make:vuecomponent`)
instead - the two systems are not meant to be mixed on the same mount point.
