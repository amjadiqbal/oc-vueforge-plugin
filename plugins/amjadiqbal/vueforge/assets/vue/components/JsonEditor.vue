<script setup lang="ts">
/**
 * JsonEditor - example VueForge component demonstrating nested key-value
 * object state, backed by a JSON object column.
 */
import { ref, watch } from 'vue';
import { useVueForge } from '../../js/composables/useVueForge';

type JsonObject = Record<string, unknown>;

interface Props {
    modelValue?: JsonObject;
    fieldName: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({}),
});

const { value: data } = useVueForge<JsonObject>({
    initialValue: props.modelValue && typeof props.modelValue === 'object' ? { ...props.modelValue } : {},
    fieldName: props.fieldName,
});

interface Row {
    key: string;
    value: string;
}

function toRows(obj: JsonObject): Row[] {
    return Object.entries(obj).map(([key, value]) => ({
        key,
        value: typeof value === 'string' ? value : JSON.stringify(value),
    }));
}

const rows = ref<Row[]>(toRows(data.value));

// `rows` is the single source of truth for user edits; it is folded back
// into `data` (and from there into the hidden input, via useVueForge) on
// every change. `data` is deliberately NOT watched in the other direction -
// doing so previously created an infinite update loop, since assigning
// `data.value` here would re-trigger a `data` watcher synchronously within
// the same reactive flush, which would then reassign `rows`, and so on.
watch(
    rows,
    (nextRows) => {
        const next: JsonObject = {};

        for (const row of nextRows) {
            if (row.key.trim() === '') {
                continue;
            }
            next[row.key] = parseScalar(row.value);
        }

        data.value = next;
    },
    { deep: true }
);

function parseScalar(raw: string): unknown {
    if (raw === 'true') return true;
    if (raw === 'false') return false;
    if (raw !== '' && !Number.isNaN(Number(raw))) return Number(raw);
    return raw;
}

function addRow(): void {
    rows.value = [...rows.value, { key: '', value: '' }];
}

function removeRow(index: number): void {
    rows.value = rows.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="vueforge-json-editor">
        <div v-for="(row, index) in rows" :key="index" class="vueforge-json-editor__row">
            <input v-model="row.key" type="text" class="form-control" placeholder="key" />
            <input v-model="row.value" type="text" class="form-control" placeholder="value" />
            <button type="button" class="btn btn-secondary vueforge-json-editor__remove" aria-label="Remove row" @click="removeRow(index)">
                &times;
            </button>
        </div>
        <button type="button" class="btn btn-primary btn-sm" @click="addRow">+ Add field</button>
    </div>
</template>

<style scoped>
.vueforge-json-editor__row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 6px;
    margin-bottom: 6px;
    align-items: center;
}

.vueforge-json-editor__remove {
    line-height: 1;
}
</style>
