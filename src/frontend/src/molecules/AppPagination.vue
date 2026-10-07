<script setup lang="ts">
import Paginator from 'primevue/paginator';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    currentPage: number;
    lastPage: number;
    total: number;
    perPage?: number;
    rowsPerPageOptions?: number[];
  }>(),
  {
    perPage: 15,
    rowsPerPageOptions: () => [],
  },
);

const emit = defineEmits<{
  'page-change': [page: number];
  'per-page-change': [perPage: number];
}>();

const first = computed(() => (props.currentPage - 1) * props.perPage);

// Show the rows-per-page dropdown + current-page report only when the caller
// opts in with rowsPerPageOptions. Keeps the minimal template the other list
// pages rely on untouched.
const template = computed(() => {
  if (props.rowsPerPageOptions.length > 0) {
    return 'RowsPerPageDropdown CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink';
  }
  return 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink';
});

// Hide when there's no pagination AND no per-page control to render.
const visible = computed(() => props.lastPage > 1 || props.rowsPerPageOptions.length > 0);
</script>

<template>
  <Paginator
    v-if="visible"
    :rows="perPage"
    :total-records="total"
    :first="first"
    :rows-per-page-options="rowsPerPageOptions.length > 0 ? rowsPerPageOptions : undefined"
    :template="template"
    current-page-report-template="{first}–{last} de {totalRecords}"
    @page="
      (e: any) => {
        // Paginator emits both rows and page together when the user changes
        // rows-per-page (it resets to page 1). Emit per-page-change exclusively
        // in that case so the parent doesn't fire two back-to-back fetches.
        if (e.rows !== perPage) emit('per-page-change', e.rows);
        else emit('page-change', e.page + 1);
      }
    "
  />
</template>
