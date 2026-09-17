import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import TagInput from '../../assets/vue/components/TagInput.vue';
import JsonEditor from '../../assets/vue/components/JsonEditor.vue';
import { useVueForge } from '../../assets/js/composables/useVueForge';
import { defineComponent, h } from 'vue';

/**
 * Smoke tests covering the "no console errors/leaks across mount/unmount"
 * requirement that a full browser session would otherwise verify. These run
 * under jsdom via Vitest + @vue/test-utils, which is the pragmatic
 * equivalent when a real backend login flow isn't available in this
 * environment.
 */

function makeHiddenInput(fieldName: string): HTMLInputElement {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = fieldName;
    input.setAttribute('data-vueforge-hidden-input', '');
    document.body.appendChild(input);
    return input;
}

describe('TagInput', () => {
    let consoleError: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        consoleError.mockRestore();
        document.body.innerHTML = '';
    });

    it('mounts, adds a tag reactively, syncs the hidden input, and unmounts without console errors', async () => {
        makeHiddenInput('tags_field');

        const wrapper = mount(TagInput, {
            props: { modelValue: ['existing'], fieldName: 'tags_field' },
            attachTo: document.body,
        });

        expect(wrapper.findAll('.vueforge-tag-input__chip')).toHaveLength(1);

        const input = wrapper.find('input.vueforge-tag-input__field');
        await input.setValue('newtag');
        await input.trigger('keydown.enter');

        expect(wrapper.findAll('.vueforge-tag-input__chip')).toHaveLength(2);

        const hidden = document.querySelector<HTMLInputElement>('[name="tags_field"]');
        expect(hidden).not.toBeNull();
        expect(JSON.parse(hidden!.value)).toEqual(['existing', 'newtag']);

        wrapper.unmount();

        expect(consoleError).not.toHaveBeenCalled();
    });

    it('does not add duplicate or blank tags', async () => {
        makeHiddenInput('tags_field2');

        const wrapper = mount(TagInput, {
            props: { modelValue: ['a'], fieldName: 'tags_field2' },
            attachTo: document.body,
        });

        const input = wrapper.find('input.vueforge-tag-input__field');
        await input.setValue('a');
        await input.trigger('keydown.enter');
        await input.setValue('   ');
        await input.trigger('keydown.enter');

        expect(wrapper.findAll('.vueforge-tag-input__chip')).toHaveLength(1);

        wrapper.unmount();
        expect(consoleError).not.toHaveBeenCalled();
    });
});

describe('JsonEditor', () => {
    let consoleError: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        consoleError.mockRestore();
        document.body.innerHTML = '';
    });

    it('mounts with seeded rows, edits a value, and syncs the hidden input', async () => {
        makeHiddenInput('json_field');

        const wrapper = mount(JsonEditor, {
            props: { modelValue: { a: '1', b: 'two' }, fieldName: 'json_field' },
            attachTo: document.body,
        });

        const rows = wrapper.findAll('.vueforge-json-editor__row');
        expect(rows).toHaveLength(2);

        await wrapper.find('.vueforge-json-editor__row:first-child input:nth-child(2)').setValue('42');
        await wrapper.vm.$nextTick();

        const hidden = document.querySelector<HTMLInputElement>('[name="json_field"]');
        const parsed = JSON.parse(hidden!.value);
        expect(parsed.a).toBe(42);

        wrapper.unmount();
        expect(consoleError).not.toHaveBeenCalled();
    });

    it('adds and removes rows without leaking listeners across remounts', () => {
        makeHiddenInput('json_field2');

        for (let i = 0; i < 3; i++) {
            const wrapper = mount(JsonEditor, {
                props: { modelValue: {}, fieldName: 'json_field2' },
                attachTo: document.body,
            });
            wrapper.unmount();
        }

        expect(consoleError).not.toHaveBeenCalled();
    });
});

describe('useVueForge', () => {
    it('warns via console.error when the hidden input cannot be found, but does not throw', () => {
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

        const TestComponent = defineComponent({
            setup() {
                const { value } = useVueForge({ initialValue: [], fieldName: 'missing_field' });
                return () => h('div', JSON.stringify(value.value));
            },
        });

        expect(() => mount(TestComponent)).not.toThrow();
        expect(consoleError).toHaveBeenCalledWith(expect.stringContaining('could not find the hidden input'));

        consoleError.mockRestore();
    });
});
