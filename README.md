<p align="center">
  <img src="art/banner.svg" alt="VueForge - the rapid Vue 3 component & widget engine for October CMS" width="100%">
</p>

<h1 align="center"><img src="art/icon.svg" width="28" height="28" valign="middle" alt=""> VueForge for October CMS</h1>

<p align="center">
  <a href="https://github.com/amjadiqbal/oc-vueforge-plugin/actions/workflows/tests.yml"><img src="https://github.com/amjadiqbal/oc-vueforge-plugin/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License: MIT"></a>
  <a href="CHANGELOG.md"><img src="https://img.shields.io/badge/version-0.1.8-informational.svg" alt="Version 0.1.8"></a>
</p>

The rapid Vue 3 component & widget engine for October CMS backend interfaces.

VueForge lets you drop a real Vue 3 `.vue` single-file component (Composition
API, `<script setup>`, TypeScript, compiled by Vite) into any October CMS
backend form as a `FormWidget` - with prop serialization, JSON save/load, and
a bi-directional `v-model`-style sync to the form's native HTML submit
already wired up. It is a deliberate alternative to October's own native
`VueComponentBase` system - see `docs/VUE3_MIGRATION_GUIDE.md` for how the two
differ and when to use which.

## Requirements

- October CMS 4.2+ (built and tested against 4.4.5, `october/rain` ^4.4)
- PHP 8.2+
- Node 18+ / npm, for building the frontend assets (Vite 5/6, Vue 3.4+, TypeScript)

## Quickstart

1. Install the plugin into `plugins/amjadiqbal/vueforge` (see the Installation
   section below).
2. Build its frontend assets once:

   ```bash
   cd plugins/amjadiqbal/vueforge
   npm install
   npm run build
   ```

3. Use the `vueforge` widget type in any `fields.yaml`:

   ```yaml
   tags:
       label: Tags
       type: vueforge
       component: TagInput
       props:
           placeholder: "Add a tag..."

   metadata:
       label: Metadata
       type: vueforge
       component: JsonEditor
   ```

   The field's underlying model attribute should be a JSON-castable column
   (`protected $jsonable = ['tags', 'metadata'];` on the model, or a native
   JSON column).

That's it - `TagInput` (array state) and `JsonEditor` (nested key/value
object state) ship as working examples in
`assets/vue/components/`.

## Architecture

```
formwidgets/VueWidget.php     FormWidgetBase subclass: renders the mount
                              point, serializes props, sanitizes save data.
classes/ViteResolver.php      Resolves the right asset (dev server or built
                              manifest.json chunk) for a widget's Vite entry.
assets/js/vueforge.ts         ESM hydrator: finds [data-vueforge-widget]
                              elements, dynamically imports and mounts the
                              matching component.
assets/js/composables/
  useVueForge.ts              Bi-directional v-model <-> hidden <input> sync.
  useOctoberAjax.ts           Typed wrapper around October's window.jax AJAX API.
assets/vue/components/
  TagInput.vue                Example: array state (chips/tags).
  JsonEditor.vue               Example: nested key/value object state.
console/MakeVueWidget.php     `php artisan vueforge:make` generator.
```

### How a value round-trips

1. **Load**: `VueWidget::prepareVars()` reads the model attribute via
   October's normal `getLoadValue()`, JSON-encodes it (HTML-attribute-escaped
   - see the migration guide, item 9), and renders it into
   `data-vueforge-props` plus a seed `<input type="hidden" value="...">`.
2. **Hydrate**: `vueforge.ts` finds the mount point, dynamically imports the
   named component, and mounts it with the parsed props.
3. **Edit**: the component uses `useVueForge()` to get a reactive `value`
   ref; every change re-serializes it into the hidden input's `value`.
4. **Save**: October's normal form submit posts the hidden input's JSON
   string; `VueWidget::getSaveValue()` parses and sanitizes it (rejecting
   malformed JSON and stripping anything that isn't plain array/scalar data)
   before Eloquent persists it.

## YAML field configuration reference

| Key | Type | Default | Description |
|---|---|---|---|
| `component` | string | `JsonEditor` | The Vue component to mount. Must be registered in `vueforge.ts`'s component map (or via `registerVueForgeComponent()` for your own components). |
| `viteEntry` | string | `assets/vue/components/{component}.vue` | Source path used to look up the built asset in Vite's manifest. |
| `props` | array | `[]` | Extra static props merged with the field's current value (passed as `modelValue`) before being handed to the component. |

## Artisan CLI: scaffolding your own widget

```bash
php artisan vueforge:make Acme.Blog TagList
```

Generates:

- `plugins/acme/blog/formwidgets/TagList.php` - a `VueWidget` subclass with
  `$component`/`$viteEntry` pre-filled.
- `plugins/acme/blog/assets/vue/TagList.vue` - a `<script setup lang="ts">`
  stub already wired to `useVueForge()`.

and prints the YAML `type:` code to use. Register the generated FormWidget in
your plugin's `Plugin::registerFormWidgets()` as usual.

Note: the generated component imports `useVueForge` via a relative path
across plugin folders. This only resolves in your own plugin's Vite build if
its dev server is configured with `server.fs.allow` including the VueForge
plugin's directory (Vite restricts serving files outside its project root by
default) - see `docs/VUE3_MIGRATION_GUIDE.md`.

## Installation

Plugin code: `AmjadIqbal.VueForge`. Composer package: `amjadiqbal/vueforge-plugin`
- same vendor identity as every other package from this author (GitHub,
Packagist, WordPress.org). **This plugin is not eligible for an October CMS
Marketplace listing under this account** - October's Marketplace author code
for this account is a separate, permanent identity (`Amjad`, confirmed by a
real rejected submission), and a plugin's Composer vendor and its Marketplace
author code cannot diverge without breaking `composer require` for anyone who
installs it (`composer/installers` derives the install path only from the
Composer package's own vendor prefix, with no override - a mismatch means
October's plugin loader looks for a class that doesn't exist at that path and
the plugin silently never loads). See `CHANGELOG.md` (0.1.7) for the full
reasoning and the history of this decision.

**Via Composer** (once published to Packagist):
```bash
composer require amjadiqbal/vueforge-plugin
```

**Manual install** (works today):

1. Clone this repository directly into your October CMS application's
   `plugins/amjadiqbal/vueforge` directory (i.e. this repo's root becomes
   that directory - do not nest it any further).
2. `cd plugins/amjadiqbal/vueforge && npm install && npm run build`.
3. `php artisan october:migrate` (no migrations ship with this plugin, but
   this refreshes the plugin registry so the widget/console command appear).

## Testing

- **PHP**: `vendor/bin/phpunit` from your October application root, scoped to
  this plugin's `tests/` directory (see the docblock in
  `tests/VueWidgetTest.php` for the exact invocation and why a bare
  `Backend\Classes\FormField` + `VueWidget` pair is used instead of a full
  `Backend\Widgets\Form`).
- **TypeScript**: `npm run typecheck` (`vue-tsc --noEmit`).
- **JS component behavior**: `npm test` (Vitest + `@vue/test-utils` + jsdom) -
  covers mount/prop-hydration/hidden-input sync/unmount for both example
  components.
- **Build**: `npm run build` (Vite production build; fails the whole `build`
  script if `vue-tsc --noEmit` reports errors first).

## Why `assets/dist/` is not committed

The built Vite output (`assets/dist/`, including `manifest.json`) is
`.gitignore`d. `ViteResolver` requires a real build to resolve production
assets, so **run `npm run build` after installing this plugin** - there is no
built-in fallback. This mirrors how most modern Vite-based October plugins in
the ecosystem ship (source in git, build as an install step), and avoids
committing machine-generated, hashed filenames that differ per Node/Vite
version.

## License

MIT - see `LICENSE`.
