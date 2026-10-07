<script setup lang="ts" generic="T">
import { useI18n } from 'vue-i18n';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const { t } = useI18n();

defineProps<{
  columns: { key: string; label: string; sortable?: boolean; width?: string }[];
  data: T[];
  loading?: boolean;
  emptyText?: string;
  clickable?: boolean;
}>();

defineEmits<{
  'row-click': [item: T];
}>();
</script>

<template>
  <DataTable
    :value="data"
    :loading="loading"
    size="small"
    :selection-mode="clickable ? 'single' : undefined"
    @row-click="(e: any) => $emit('row-click', e.data)"
  >
    <template #empty>{{ emptyText ?? t('common.noData') }}</template>
    <Column
      v-for="col in columns"
      :key="col.key"
      :field="col.key"
      :header="col.label"
      :sortable="col.sortable"
      :style="col.width ? { width: col.width } : {}"
    >
      <template #body="{ data: item }">
        <slot :name="`cell-${col.key}`" :item="item" :value="(item as any)[col.key]">
          {{ (item as any)[col.key] }}
        </slot>
      </template>
    </Column>
  </DataTable>
</template>
