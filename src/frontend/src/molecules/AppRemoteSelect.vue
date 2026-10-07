<script setup lang="ts">
/**
 * Select whose options come from the API as the user types. Binds the option
 * VALUE (an id), not the object, so it plugs straight into a form payload.
 *
 *   <AppRemoteSelect
 *     v-model="form.data.manager_id"
 *     :fetch-options="(q) => userService.options({ search: q })"
 *     :initial-options="manager ? [{ value: manager.id, label: manager.name }] : []"
 *   />
 *
 * Dependent selects: pass the parent value in `params`; when it changes the
 * cached options are dropped and (by default) the selection is cleared, the
 * way a "department" select resets when the "business unit" changes.
 *
 * Inline create: with `creatable`, the dropdown offers "Crear «texto»" and
 * emits `create` with the typed text; the parent opens its form and, once
 * saved, sets v-model to the new id (passing it in `initialOptions` so the
 * label shows without another search).
 */
import AutoComplete, { type AutoCompleteCompleteEvent } from 'primevue/autocomplete';
import Button from 'primevue/button';
import { computed, ref, watch } from 'vue';

export interface RemoteOption {
  value: string | number;
  label: string;
  /** Optional second line (email, code…). */
  description?: string;
}

type Value = string | number | null;

const props = withDefaults(
  defineProps<{
    modelValue: Value | Value[];
    /** Returns the options matching `query` ('' = first page). `params` is passed through. */
    fetchOptions: (query: string, params: Record<string, unknown>) => Promise<RemoteOption[]>;
    /** Options for values already selected (edit forms), so their labels show without a search. */
    initialOptions?: RemoteOption[];
    /** Extra filter (e.g. the parent select's value). Changing it resets the options. */
    params?: Record<string, unknown>;
    /** Clear the selection when `params` changes. */
    clearOnParamsChange?: boolean;
    multiple?: boolean;
    creatable?: boolean;
    placeholder?: string;
    inputId?: string;
    invalid?: boolean;
    disabled?: boolean;
    /** Characters needed before searching (0: the dropdown button lists the first page). */
    minLength?: number;
  }>(),
  {
    initialOptions: () => [],
    params: () => ({}),
    clearOnParamsChange: true,
    multiple: false,
    creatable: false,
    placeholder: 'Buscar…',
    inputId: undefined,
    invalid: false,
    disabled: false,
    minLength: 0,
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: Value | Value[]];
  create: [query: string];
}>();

const suggestions = ref<RemoteOption[]>([]);
const loading = ref(false);
const lastQuery = ref('');
/** Every option seen, by value, so the selection keeps its label across searches. */
const known = ref(new Map<Value, RemoteOption>());
let requestSeq = 0;

function remember(options: RemoteOption[]) {
  for (const option of options) known.value.set(option.value, option);
}
remember(props.initialOptions);
watch(() => props.initialOptions, remember);

function toOption(value: Value): RemoteOption | null {
  if (value === null || value === undefined || value === '') return null;
  return known.value.get(value) ?? { value, label: String(value) };
}

// AutoComplete binds objects; this component exposes values.
const selected = computed({
  get: () => {
    if (props.multiple) {
      const values = Array.isArray(props.modelValue) ? props.modelValue : [];
      return values.map(toOption).filter((o): o is RemoteOption => o !== null);
    }
    return toOption(Array.isArray(props.modelValue) ? null : props.modelValue);
  },
  set: (picked: RemoteOption | RemoteOption[] | string | null) => {
    if (props.multiple) {
      const list = Array.isArray(picked) ? picked : [];
      remember(list);
      emit(
        'update:modelValue',
        list.map((o) => o.value),
      );
      return;
    }
    // While typing, AutoComplete passes the raw text: not a selection.
    if (typeof picked === 'string') return;
    if (picked && !Array.isArray(picked)) {
      remember([picked]);
      emit('update:modelValue', picked.value);
    } else {
      emit('update:modelValue', null);
    }
  },
});

async function search(event: AutoCompleteCompleteEvent) {
  const query = event.query.trim();
  lastQuery.value = query;
  const seq = ++requestSeq;
  loading.value = true;
  try {
    const options = await props.fetchOptions(query, props.params);
    // A slower, older request must not overwrite a newer one.
    if (seq !== requestSeq) return;
    remember(options);
    suggestions.value = options;
  } catch {
    if (seq === requestSeq) suggestions.value = [];
  } finally {
    if (seq === requestSeq) loading.value = false;
  }
}

watch(
  () => JSON.stringify(props.params),
  () => {
    suggestions.value = [];
    if (props.clearOnParamsChange) emit('update:modelValue', props.multiple ? [] : null);
  },
);

function create() {
  emit('create', lastQuery.value);
}
</script>

<template>
  <AutoComplete
    v-model="selected"
    :suggestions="suggestions"
    option-label="label"
    :multiple="multiple"
    :input-id="inputId"
    :placeholder="placeholder"
    :invalid="invalid"
    :disabled="disabled"
    :loading="loading"
    :min-length="minLength"
    :delay="250"
    dropdown
    force-selection
    fluid
    empty-search-message="Sin resultados"
    @complete="search"
  >
    <template #option="{ option }">
      <div class="remote-option">
        <span>{{ option.label }}</span>
        <small v-if="option.description" class="remote-option__description">{{
          option.description
        }}</small>
      </div>
    </template>
    <template v-if="creatable" #footer>
      <div class="remote-footer">
        <Button
          :label="lastQuery ? `Crear «${lastQuery}»` : 'Crear nuevo'"
          icon="pi pi-plus"
          text
          size="small"
          @click="create"
        />
      </div>
    </template>
  </AutoComplete>
</template>

<style scoped>
.remote-option {
  display: flex;
  flex-direction: column;
}

.remote-option__description {
  color: var(--p-text-muted-color);
}

.remote-footer {
  border-top: 1px solid var(--app-surface-border);
  padding: 4px;
}
</style>
