import { ref, watch, type Ref } from 'vue';

export interface UseVueForgeOptions<T> {
    /** The value seeded from PHP via the component's `modelValue` prop. */
    initialValue: T;
    /** The `name` attribute of the hidden input October's form submit reads. */
    fieldName: string;
    /** Root element to search for the hidden input; defaults to `document`. */
    root?: ParentNode;
}

/**
 * useVueForge provides bi-directional v-model-style state for a VueForge
 * component: a reactive `value` ref seeded from the PHP-rendered prop, kept
 * in sync with the hidden `<input>` that October's native (non-AJAX aware)
 * form submit serializes on save.
 *
 * The hidden input is looked up by `data-vueforge-hidden-input` scoped to
 * the field name rather than by a global selector, so multiple VueForge
 * widgets on the same form (e.g. inside a Repeater) never collide.
 */
export function useVueForge<T>(options: UseVueForgeOptions<T>): { value: Ref<T> } {
    const { initialValue, fieldName, root = document } = options;

    const value = ref(initialValue) as Ref<T>;

    const input = findHiddenInput(root, fieldName);

    if (!input) {
        // eslint-disable-next-line no-console
        console.error(`VueForge: could not find the hidden input for field "${fieldName}". Form submit will not include this widget's value.`);
    } else {
        // Seed the input in case the PHP-rendered value and the JS-parsed
        // value ever diverge (e.g. due to type coercion during JSON parse).
        syncInput(input, value.value);
    }

    watch(
        value,
        (next) => {
            if (input) {
                syncInput(input, next);
            }
        },
        { deep: true }
    );

    return { value };
}

function findHiddenInput(root: ParentNode, fieldName: string): HTMLInputElement | null {
    const candidates = root.querySelectorAll<HTMLInputElement>('[data-vueforge-hidden-input]');

    for (const candidate of Array.from(candidates)) {
        if (candidate.getAttribute('name') === fieldName) {
            return candidate;
        }
    }

    return null;
}

function syncInput(input: HTMLInputElement, value: unknown): void {
    input.value = JSON.stringify(value ?? null);
    // Some third-party field-watchers (change-tracking, "unsaved changes"
    // banners) listen for a native `input`/`change` event.
    input.dispatchEvent(new Event('input', { bubbles: true }));
}
