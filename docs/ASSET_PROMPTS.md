# VueForge marketing/listing assets

**Final graphics belong in `../design/`** (the product folder one level above
`src/`, i.e. `marketplaces/octobercms/design/`), **not in this plugin repo**.
This file only records the dimensions, target locations, and AI image-gen
prompts to use when producing them - it is documentation, not a deliverable.

## 1. Banner - 1280x640

**Where it's used:** October CMS Marketplace plugin listing header image.

**Prompt:**
> A clean, modern software product banner, 1280x640px, for a developer tool
> called "VueForge" - a Vue 3 component engine for October CMS. Dark navy
> background (#0f172a) with a subtle geometric grid pattern. Center-left: the
> text "VueForge" in a bold, modern sans-serif (e.g. Inter/Manrope), white,
> with the tagline "Vue 3 widgets for October CMS" beneath it in a muted
> blue-gray. Center-right: an abstract illustration suggesting a component
> tree / widget assembly - interlocking rounded rectangles in October CMS's
> orange (#ff6600, used sparingly as an accent) and Vue's green (#42b883).
> No photorealistic elements, no other text, flat vector illustration style,
> generous negative space, professional SaaS-product aesthetic.

## 2. Icon - 512x512

**Where it's used:** Marketplace plugin icon, plugin directory listing thumbnail.

**Prompt:**
> A minimalist app icon, 512x512px, rounded-square (squircle) container,
> dark navy background (#0f172a). Centered: a simplified "V" monogram built
> from two overlapping rounded chevrons, one in Vue's green (#42b883) and
> one in October CMS's orange (#ff6600), suggesting both "Vue" and "forge/
> anvil". Flat design, no gradients, no text, works recognizably at 48x48px.

## 3. Screenshots (3-5 recommended)

**Where they're used:** Marketplace listing gallery, README.

| # | Target location | Dimensions | What to capture |
|---|---|---|---|
| 1 | Listing gallery #1 | 1280x800 | The `TagInput` example widget rendered inside a real backend form, with a few tags already added and the cursor mid-typing a new one. |
| 2 | Listing gallery #2 | 1280x800 | The `JsonEditor` example widget with 3-4 key/value rows filled in, showing the "+ Add field" button. |
| 3 | Listing gallery #3 | 1280x800 | A `fields.yaml` snippet (syntax-highlighted, e.g. in VS Code or a code-block screenshot) showing how little YAML is needed to add a VueForge widget. |
| 4 (optional) | Listing gallery #4 | 1280x800 | Terminal output of `php artisan vueforge:make Acme.Blog TagList`, showing the generated-files list and YAML usage hint. |
| 5 (optional) | Listing gallery #5 | 1280x800 | Split-screen: the backend form widget on one side, the saved JSON value visible in a database browser (e.g. TablePlus/phpMyAdmin) on the other, illustrating the save round-trip. |

Screenshots should be captured from a real running October CMS backend (not
mocked/staged), per this project's own verification standard - see
`PROJECT_PROGRESS.md` for what was verified during development and could be
reused for these captures.
