<script setup lang="ts" generic="T">
import { useI18n } from 'vue-i18n';
import DataTable, { type DataTableSortEvent } from 'primevue/datatable';
import Column from 'primevue/column';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';
import AppSearchBar from './AppSearchBar.vue';
import AppPagination from './AppPagination.vue';
import { computed, ref, watch } from 'vue';
import { ROWS_PER_PAGE_OPTIONS } from '@/core/constants/pagination';

export interface CrudColumn {
  key: string;
  label: string;
  /** Backend sort key. Defaults to `key`. Only used when `sortable` is true. */
  sortField?: string;
  sortable?: boolean;
  /** Hidden by default but available in the column picker. Defaults to true. */
  defaultVisible?: boolean;
  /** When false, column is always rendered and hidden from the picker. */
  toggleable?: boolean;
  width?: string;
  align?: 'left' | 'center' | 'right';
}

const props = withDefaults(
  defineProps<{
    columns: CrudColumn[];
    data: T[];
    loading?: boolean;
    search: string;
    searchPlaceholder?: string;
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
    sortBy: string | null;
    sortDir: 'asc' | 'desc';
    /** localStorage namespace for column visibility. Should match useDataTable's persistKey. */
    persistKey?: string;
    rowsPerPageOptions?: number[];
    emptyText?: string;
    rowClickable?: boolean;
    /** Render the trailing actions column (slot `actions`). */
    hasActions?: boolean;
    actionsWidth?: string;
    /** Bound to the table's selection (use `v-model:selection`). */
    selection?: T[] | T | null;
    selectionMode?: 'single' | 'multiple';
    dataKey?: string;
    /** PrimeVue cell/row inline edit mode. Pair with `editor-{key}` slots. */
    editMode?: 'cell' | 'row';
    /** Filas desplegables: agrega la columna de flecha y usa el slot `expansion`. */
    expandable?: boolean;
  }>(),
  {
    rowsPerPageOptions: () => ROWS_PER_PAGE_OPTIONS,
    emptyText: undefined,
    searchPlaceholder: undefined,
    rowClickable: false,
    hasActions: true,
    actionsWidth: '110px',
    dataKey: 'id',
    expandable: false,
    persistKey: undefined,
    selection: undefined,
    selectionMode: undefined,
    editMode: undefined,
  },
);

// Filas desplegadas (PrimeVue las indexa por `dataKey`).
const expandedRows = ref<Record<string, boolean>>({});

const { t } = useI18n();

const emit = defineEmits<{
  'update:search': [value: string];
  'update:selection': [value: T[] | T | null];
  'page-change': [page: number];
  'per-page-change': [perPage: number];
  sort: [field: string | null, dir: 'asc' | 'desc'];
  'row-click': [item: T];
  'cell-edit-complete': [
    event: { field: string; data: T; newValue: unknown; originalEvent: Event },
  ];
}>();

const searchProxy = computed({
  get: () => props.search,
  set: (v: string) => emit('update:search', v),
});

const columnsKey = computed(() => (props.persistKey ? `${props.persistKey}__columns` : null));
const allKeys = computed(() => props.columns.map((c) => c.key));
const defaultVisible = computed(() =>
  props.columns.filter((c) => c.defaultVisible !== false).map((c) => c.key),
);
const toggleableColumns = computed(() => props.columns.filter((c) => c.toggleable !== false));

function readPersistedColumns(): string[] {
  const key = columnsKey.value;
  if (!key) return defaultVisible.value;
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return defaultVisible.value;
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      const filtered = (parsed as unknown[]).filter(
        (k): k is string => typeof k === 'string' && allKeys.value.includes(k),
      );
      return filtered.length ? filtered : defaultVisible.value;
    }
    return defaultVisible.value;
  } catch {
    return defaultVisible.value;
  }
}

const visibleColumns = ref<string[]>(readPersistedColumns());

watch(
  visibleColumns,
  (val) => {
    const key = columnsKey.value;
    if (!key) return;
    try {
      localStorage.setItem(key, JSON.stringify(val));
    } catch {
      /* quota exceeded — silently drop */
    }
  },
  { deep: true },
);

// Always-on (toggleable: false) columns are appended after the user's picks
// so the visual order matches the descriptor without polluting the picker.
const renderedColumns = computed(() =>
  props.columns.filter((c) => c.toggleable === false || visibleColumns.value.includes(c.key)),
);

const sortOrder = computed<1 | -1 | null>(() =>
  props.sortBy ? (props.sortDir === 'asc' ? 1 : -1) : null,
);

function onSort(event: DataTableSortEvent) {
  if (typeof event.sortField !== 'string' || !event.sortOrder) {
    emit('sort', null, 'asc');
    return;
  }
  emit('sort', event.sortField, event.sortOrder === 1 ? 'asc' : 'desc');
}

/** Generic rows are typed T; read a column by key without casting in the template. */
function cellValue(item: T, key: string): unknown {
  return (item as Record<string, unknown>)[key];
}

function onRowClick(e: { data: T }) {
  if (props.rowClickable) emit('row-click', e.data);
}

function resetVisibleColumns() {
  visibleColumns.value = [...defaultVisible.value];
}
</script>

<template>
  <div class="crud-table">
    <div class="crud-toolbar">
      <AppSearchBar
        v-model="searchProxy"
        :placeholder="searchPlaceholder ?? t('common.search')"
        class="crud-search"
      />
      <div class="crud-filters">
        <slot name="filters" />
      </div>
      <MultiSelect
        v-model="visibleColumns"
        :options="toggleableColumns"
        option-label="label"
        option-value="key"
        :max-selected-labels="0"
        :selected-items-label="t('table.columnsSelected', { count: '{0}' })"
        :placeholder="t('table.columns')"
        size="small"
        class="crud-cols"
      >
        <template #header>
          <div class="crud-cols-header">
            <span>{{ t('table.visibleColumns') }}</span>
            <Button
              text
              size="small"
              :label="t('table.resetColumns')"
              icon="pi pi-refresh"
              @click="resetVisibleColumns"
            />
          </div>
        </template>
      </MultiSelect>
    </div>

    <DataTable
      v-model:expanded-rows="expandedRows"
      :value="data"
      :loading="loading"
      size="small"
      :sort-field="sortBy ?? undefined"
      :sort-order="sortOrder ?? undefined"
      removable-sort
      :selection="selection"
      :selection-mode="selectionMode === 'single' ? 'single' : undefined"
      :data-key="dataKey"
      :edit-mode="editMode"
      :class="{ 'crud-clickable': rowClickable }"
      @sort="onSort"
      @row-click="onRowClick"
      @update:selection="(v: T[] | T | null) => emit('update:selection', v)"
      @cell-edit-complete="(e: any) => emit('cell-edit-complete', e)"
    >
      <template #empty>{{ emptyText ?? t('common.noData') }}</template>

      <Column v-if="selectionMode === 'multiple'" selection-mode="multiple" style="width: 40px" />

      <Column v-if="expandable" expander style="width: 36px" />

      <Column
        v-for="col in renderedColumns"
        :key="col.key"
        :field="col.sortable ? (col.sortField ?? col.key) : undefined"
        :header="col.label"
        :sortable="col.sortable"
        :style="col.width ? { width: col.width } : {}"
        :body-class="col.align ? `align-${col.align}` : undefined"
        :header-class="col.align ? `align-${col.align}` : undefined"
      >
        <template #body="{ data: item }">
          <slot :name="`cell-${col.key}`" :item="item" :value="cellValue(item, col.key)">
            {{ cellValue(item, col.key) ?? '—' }}
          </slot>
        </template>
        <template v-if="$slots[`editor-${col.key}`]" #editor="{ data: item, field }">
          <slot :name="`editor-${col.key}`" :item="item" :field="field" />
        </template>
      </Column>

      <Column
        v-if="hasActions"
        header=""
        :style="{ width: actionsWidth }"
        body-class="align-right crud-actions-cell"
        header-class="align-right"
      >
        <template #body="{ data: item }">
          <slot name="actions" :item="item" />
        </template>
      </Column>

      <template v-if="expandable" #expansion="{ data: item }">
        <slot name="expansion" :item="item" />
      </template>
    </DataTable>

    <AppPagination
      :current-page="currentPage"
      :last-page="lastPage"
      :total="total"
      :per-page="perPage"
      :rows-per-page-options="rowsPerPageOptions"
      @page-change="(p) => emit('page-change', p)"
      @per-page-change="(n) => emit('per-page-change', n)"
    />
  </div>
</template>

<style scoped>
/* Wide tables scroll inside their own box instead of widening the page. */
.crud-table {
  min-width: 0;
}

.crud-table :deep(.p-datatable-table-container) {
  overflow-x: auto;
}

.crud-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.crud-search {
  flex: 1 1 280px;
  margin-bottom: 0;
  max-width: 420px;
}

.crud-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  flex: 1 1 auto;
}

.crud-filters:empty {
  display: none;
}

.crud-cols {
  min-width: 160px;
  margin-left: auto;
}

.crud-cols-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 10px 6px 12px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
  border-bottom: 1px solid var(--p-content-border-color);
}

.crud-clickable :deep(tbody tr) {
  cursor: pointer;
}

:deep(.align-left) {
  text-align: left;
}
:deep(.align-center) {
  text-align: center;
}
:deep(.align-right) {
  text-align: right;
}

:deep(.crud-actions-cell) {
  white-space: nowrap;
}
</style>
