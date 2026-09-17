import { createApp, type App, type Component } from 'vue';

/**
 * vueforge.ts is the ESM hydrator loaded by every VueWidget instance. It
 * scans the DOM for `[data-vueforge-widget]` mount points rendered by
 * `formwidgets/vuewidget/partials/_vueforge.php`, dynamically imports the
 * matching Vue 3 component, and mounts it with its JSON props.
 *
 * Note on October's own `$componentName`: October CMS 4.2+ ships its OWN
 * native Vue-in-the-backend mechanism (`System\Classes\VueComponentBase` +
 * `VueMaker::outputVueComponentTemplates()`), which requires every component
 * class to declare a PHP `$componentName` (a kebab-case tag, e.g.
 * "vendor-plugin-name") and ships components as plain JS objects registered
 * globally - no SFCs, no TypeScript, no build step. VueForge is a deliberate
 * alternative to that system for teams that want real `.vue` SFCs + Vite +
 * TypeScript; it does not register through VueComponentBase/VueMaker at all,
 * so October's `$componentName` requirement does not apply here - see
 * docs/VUE3_MIGRATION_GUIDE.md item #1 for the full comparison.
 */

/** Registry of built-in example components, keyed by their `data-vueforge-widget` name. */
const componentLoaders: Record<string, () => Promise<{ default: Component }>> = {
    TagInput: () => import('../vue/components/TagInput.vue'),
    JsonEditor: () => import('../vue/components/JsonEditor.vue'),
};

/**
 * registerVueForgeComponent lets a consuming plugin register its own
 * generated component (e.g. one produced by `vueforge:make`) so vueforge.ts
 * can find it by name without a hard-coded import list.
 */
export function registerVueForgeComponent(name: string, loader: () => Promise<{ default: Component }>): void {
    componentLoaders[name] = loader;
}

interface MountedInstance {
    app: App;
    el: Element;
}

const mountedInstances = new Map<Element, MountedInstance>();

function parseProps(raw: string | null): Record<string, unknown> {
    if (!raw) {
        return {};
    }

    try {
        const parsed = JSON.parse(raw);
        return typeof parsed === 'object' && parsed !== null ? parsed : {};
    } catch {
        // Malformed props must never crash the whole backend form - render
        // the component with an empty prop set instead.
        return {};
    }
}

async function mountWidget(el: Element): Promise<void> {
    if (mountedInstances.has(el)) {
        return;
    }

    const componentName = el.getAttribute('data-vueforge-widget');
    const fieldName = el.getAttribute('data-vueforge-field') ?? '';
    const propsRaw = el.getAttribute('data-vueforge-props');

    if (!componentName) {
        return;
    }

    const loader = componentLoaders[componentName];

    if (!loader) {
        // eslint-disable-next-line no-console
        console.error(`VueForge: no component registered for "${componentName}". Did you forget to call registerVueForgeComponent()?`);
        return;
    }

    const mod = await loader();
    const props = parseProps(propsRaw);

    const app = createApp(mod.default, {
        ...props,
        fieldName,
    });

    // VueForge's own convention (distinct from October's native
    // VueComponentBase $componentName - see the file header comment):
    // expose the widget's component name on the instance for debugging.
    app.config.globalProperties.$vueforgeComponent = componentName;

    const instance = app.mount(el);
    mountedInstances.set(el, { app, el });

    // Support components that are removed from the DOM (e.g. inside a
    // Repeater/Recordfinder row deleted without a full page reload).
    const observer = new MutationObserver(() => {
        if (!document.body.contains(el)) {
            unmountWidget(el);
            observer.disconnect();
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    void instance;
}

function unmountWidget(el: Element): void {
    const mounted = mountedInstances.get(el);

    if (!mounted) {
        return;
    }

    mounted.app.unmount();
    mountedInstances.delete(el);
}

function hydrateAll(root: ParentNode = document): void {
    root.querySelectorAll('[data-vueforge-widget]').forEach((el) => {
        void mountWidget(el);
    });
}

function init(): void {
    hydrateAll();

    // October's Larajax framework (v4.2+) re-renders form partials over AJAX
    // (opening a Repeater group, a RecordFinder popup, an inline relation
    // record) without a full page navigation, so new mount points can appear
    // later. `ajax:update-complete` fires once the DOM has been patched.
    document.addEventListener('ajax:update-complete', () => hydrateAll());

    // Turbo-driven page swaps (backend navigation without a hard reload)
    // fire `page:updated` after the new body is in place.
    document.addEventListener('page:updated', () => hydrateAll());
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export { hydrateAll, mountWidget, unmountWidget };
