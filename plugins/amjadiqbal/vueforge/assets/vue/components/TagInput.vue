<script setup lang="ts">
/**
 * TagInput - example VueForge component demonstrating array state.
 * Renders a chip/tag list backed by a JSON array column.
 */
import { ref } from 'vue';
import { useVueForge } from '../../js/composables/useVueForge';

interface Props {
    modelValue?: string[];
    fieldName: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    placeholder: 'Add a tag and press Enter...',
});

const { value: tags } = useVueForge<string[]>({
    initialValue: Array.isArray(props.modelValue) ? [...props.modelValue] : [],
    fieldName: props.fieldName,
});

const draft = ref('');

function addTag(): void {
    const next = draft.value.trim();

    if (next === '' || tags.value.includes(next)) {
        draft.value = '';
        return;
    }

    tags.value = [...tags.value, next];
    draft.value = '';
}

function removeTag(index: number): void {
    tags.value = tags.value.filter((_tag: string, i: number) => i !== index);
}
</script>

<template>
    <div class="vueforge-tag-input">
        <ul class="vueforge-tag-input__list">
            <li v-for="(tag, index) in tags" :key="`${tag}-${index}`" class="vueforge-tag-input__chip">
                <span>{{ tag }}</span>
                <button type="button" class="vueforge-tag-input__remove" aria-label="Remove tag" @click="removeTag(index)">
                    &times;
                </button>
            </li>
        </ul>
        <input
            v-model="draft"
            type="text"
            class="form-control vueforge-tag-input__field"
            :placeholder="placeholder"
            @keydown.enter.prevent="addTag"
        />
    </div>
</template>

<style scoped>
.vueforge-tag-input__list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    list-style: none;
    margin: 0 0 8px;
    padding: 0;
}

.vueforge-tag-input__chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--bs-secondary-bg, #eef0f4);
    font-size: 0.85em;
}

.vueforge-tag-input__remove {
    border: none;
    background: transparent;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    font-size: 1.1em;
}

.vueforge-tag-input__field {
    max-width: 320px;
}
</style>
