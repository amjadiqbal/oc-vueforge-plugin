# VueForge for October CMS

**The rapid Vue 3 component & widget engine for October CMS backend interfaces.**

VueForge bridges October CMS's PHP `FormWidget` lifecycle with real Vue 3
single-file components — `<script setup>`, TypeScript, compiled by Vite —
so you can drop a modern Vue 3 control into any backend form without
hand-wiring ESM registration, prop serialization, or AJAX plumbing. It's a
deliberate alternative to October's own native `VueComponentBase` system;
see [`docs/VUE3_MIGRATION_GUIDE.md`](docs/VUE3_MIGRATION_GUIDE.md) for how
the two differ and when to use which.

```yaml
tags:
    label: Tags
    type: vueforge
    component: TagInput
```

That's the entire YAML needed to mount a real Vue 3 component, with
bi-directional state sync to the form's native submit, already wired up.

## Where things live

This repository's payload is the plugin itself, at
[`plugins/amjadiqbal/vueforge/`](plugins/amjadiqbal/vueforge/) — that's
where it needs to sit inside an October CMS install (`plugins/<vendor>/<plugin>/`),
and it's also the Composer package root.

**Start here → [`plugins/amjadiqbal/vueforge/README.md`](plugins/amjadiqbal/vueforge/README.md)**
for installation, the YAML field reference, architecture, and testing.

| Path | What it is |
|---|---|
| [`plugins/amjadiqbal/vueforge/`](plugins/amjadiqbal/vueforge/) | The plugin — PHP, TypeScript/Vue source, tests, its own README |
| [`docs/VUE3_MIGRATION_GUIDE.md`](docs/VUE3_MIGRATION_GUIDE.md) | October CMS v4.2+'s Vue 2 → Vue 3 / ESM breaking changes, confirmed against real source |
| [`docs/MARKETPLACE_CHECKLIST.md`](docs/MARKETPLACE_CHECKLIST.md) | Outstanding steps before Packagist / October Marketplace submission |
| [`docs/ASSET_PROMPTS.md`](docs/ASSET_PROMPTS.md) | Banner/icon/screenshot specs and generation prompts |
| [`PROJECT_PROGRESS.md`](PROJECT_PROGRESS.md) | Build log: what was done, what was genuinely verified and how |
| [`CHANGELOG.md`](CHANGELOG.md) | Release history |

## Status

`v0.1.0` — built and tested against a real October CMS 4.4.5 install (14/14
PHPUnit, 5/5 Vitest, clean `vue-tsc`, passing Vite build). Not yet published
to Packagist or the October CMS Marketplace — see `MARKETPLACE_CHECKLIST.md`
for what's left.

## License

MIT — see [`LICENSE`](LICENSE).
