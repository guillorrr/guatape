<script setup lang="ts">
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import AppCrudTable, { type CrudColumn } from '@/molecules/AppCrudTable.vue';
import AppModal from '@/molecules/AppModal.vue';
import { useDataTable } from '@/composables/useDataTable';
import { useFormatters } from '@/composables/useFormatters';
import {
  activityService,
  type ActivityStats,
  type JobRun,
  type RunStatus,
  type ScheduledTask,
} from '@/core/services/activity.service';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const PERSIST_KEY = 'system.activity';
/** The page polls while open: background work changes without user action. */
const REFRESH_MS = 15000;

const { formatDateTime, formatRelativeAge } = useFormatters();

const table = useDataTable<JobRun>({
  fetchFn: (params) => activityService.list(params),
  defaultSortBy: 'id',
  defaultSortDir: 'desc',
  persistKey: PERSIST_KEY,
});

const stats = ref<ActivityStats | null>(null);
const tasks = ref<ScheduledTask[]>([]);

async function loadOverview() {
  const [s, t] = await Promise.allSettled([activityService.stats(), activityService.schedule()]);
  if (s.status === 'fulfilled') stats.value = s.value.data.data;
  if (t.status === 'fulfilled') tasks.value = t.value.data.data;
}

const descriptionByName = computed<Record<string, string>>(() =>
  Object.fromEntries(
    tasks.value.filter((t) => t.description).map((t) => [t.name, t.description as string]),
  ),
);

// ---- filters ---------------------------------------------------------------
const domainOptions = computed(() =>
  Object.keys(stats.value?.by_domain ?? {}).map((d) => ({ label: d, value: d })),
);
const STATUSES: { label: string; value: RunStatus }[] = [
  { label: 'Completado', value: 'completed' },
  { label: 'Falló', value: 'failed' },
  { label: 'Corriendo', value: 'running' },
];

function filterModel(key: 'domain' | 'status') {
  return computed({
    get: () => (table.filters[key] as string | undefined) ?? null,
    set: (value: string | null) => (value ? table.setFilter(key, value) : table.clearFilter(key)),
  });
}
const domain = filterModel('domain');
const status = filterModel('status');

const columns: CrudColumn[] = [
  { key: 'created_at', label: 'Cuándo', width: '150px' },
  { key: 'domain', label: 'Dominio', width: '120px' },
  { key: 'name', label: 'Proceso', sortable: true, toggleable: false },
  { key: 'summary', label: 'Resultado', toggleable: false },
  { key: 'status', label: 'Estado', width: '110px' },
  { key: 'duration_ms', label: 'Duración', sortable: true, align: 'right', width: '100px' },
];

function formatMs(ms: number | null | undefined) {
  if (ms == null) return '—';
  return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`;
}
function statusSeverity(s: RunStatus) {
  return s === 'completed' ? 'success' : s === 'failed' ? 'danger' : 'warn';
}
function statusLabel(s: RunStatus) {
  return s === 'completed' ? 'OK' : s === 'failed' ? 'Falló' : 'Corriendo';
}

// ---- detail ----------------------------------------------------------------
const detail = ref<JobRun | null>(null);
async function openDetail(run: JobRun) {
  detail.value = run;
  try {
    // The list omits the log; fetch the full row.
    detail.value = (await activityService.show(run.id)).data.data;
  } catch {
    /* keep the list row */
  }
}

let timer: ReturnType<typeof setInterval> | undefined;
async function refresh() {
  await Promise.all([table.fetch(), loadOverview()]);
}
onMounted(() => {
  refresh();
  timer = setInterval(refresh, REFRESH_MS);
});
onUnmounted(() => timer && clearInterval(timer));
</script>

<template>
  <div class="page">
    <header class="page__header">
      <h1>Actividad</h1>
      <Button label="Actualizar" icon="pi pi-refresh" size="small" text @click="refresh" />
    </header>

    <div v-if="stats" class="stats">
      <div class="stat stat--ok">
        <span>{{ stats.completed }}</span
        ><small>completados (24 h)</small>
      </div>
      <div class="stat" :class="{ 'stat--bad': stats.failed > 0 }">
        <span>{{ stats.failed }}</span
        ><small>fallados (24 h)</small>
      </div>
      <div class="stat stat--run">
        <span>{{ stats.running }}</span
        ><small>corriendo ahora</small>
      </div>
    </div>

    <h2 class="section">
      Tareas programadas <small>lo que corre solo (routes/console.php)</small>
    </h2>
    <div v-if="tasks.length" class="tasks">
      <div v-for="task in tasks" :key="task.name" class="task">
        <div>
          <code class="task__name">{{ task.name }}</code>
          <p class="task__desc">
            {{
              task.description || 'Sin descripción: agregá ->description() en routes/console.php.'
            }}
          </p>
        </div>
        <div class="task__meta">
          <span
            ><i class="pi pi-clock" /> <code>{{ task.expression }}</code> ({{
              task.timezone
            }})</span
          >
          <span class="task__last">
            <template v-if="task.last_run">
              <Tag
                :value="statusLabel(task.last_run.status)"
                :severity="statusSeverity(task.last_run.status)"
              />
              {{ formatRelativeAge(task.last_run.finished_at) }}
            </template>
            <span v-else class="muted">todavía no corrió</span>
          </span>
          <span v-if="task.next_run" class="muted"
            >próxima: {{ formatDateTime(task.next_run) }}</span
          >
        </div>
      </div>
    </div>
    <p v-else class="muted">No hay tareas programadas.</p>

    <h2 class="section">Historial</h2>
    <AppCrudTable
      v-model:search="table.search.value"
      :columns="columns"
      :data="table.data.value"
      :loading="table.loading.value"
      :current-page="table.currentPage.value"
      :last-page="table.lastPage.value"
      :total="table.total.value"
      :per-page="table.perPage.value"
      :sort-by="table.sortBy.value"
      :sort-dir="table.sortDir.value"
      :persist-key="PERSIST_KEY"
      actions-width="60px"
      search-placeholder="Buscar proceso o resultado…"
      empty-text="Sin actividad registrada todavía"
      @page-change="table.goToPage"
      @per-page-change="table.setPerPage"
      @sort="table.setSort"
    >
      <template #filters>
        <Select
          v-model="domain"
          :options="domainOptions"
          option-label="label"
          option-value="value"
          placeholder="Dominio"
          show-clear
          size="small"
        />
        <Select
          v-model="status"
          :options="STATUSES"
          option-label="label"
          option-value="value"
          placeholder="Estado"
          show-clear
          size="small"
        />
      </template>
      <template #cell-created_at="{ item }">{{ formatDateTime(item.created_at) }}</template>
      <template #cell-summary="{ item }">
        <span v-if="item.summary">{{ item.summary }}</span>
        <span v-else class="muted">{{ descriptionByName[item.name] ?? '—' }}</span>
      </template>
      <template #cell-status="{ item }">
        <Tag :value="statusLabel(item.status)" :severity="statusSeverity(item.status)" />
      </template>
      <template #cell-duration_ms="{ item }">{{ formatMs(item.duration_ms) }}</template>
      <template #actions="{ item }">
        <Button
          v-tooltip.top="'Ver detalle'"
          icon="pi pi-eye"
          text
          rounded
          size="small"
          aria-label="Ver detalle"
          @click="openDetail(item)"
        />
      </template>
    </AppCrudTable>

    <AppModal :show="detail !== null" title="Detalle del proceso" size="lg" @close="detail = null">
      <div v-if="detail" class="detail">
        <p>
          <strong>{{ detail.name }}</strong> · {{ detail.domain }} · cola {{ detail.queue ?? '—'
          }}<br />
          {{ formatDateTime(detail.created_at) }} · {{ formatMs(detail.duration_ms) }} ·
          <Tag :value="statusLabel(detail.status)" :severity="statusSeverity(detail.status)" />
        </p>
        <p v-if="detail.summary"><strong>Resultado:</strong> {{ detail.summary }}</p>
        <template v-if="detail.exception">
          <h3>Error</h3>
          <pre class="detail__error">{{ detail.exception }}</pre>
        </template>
        <template v-if="detail.log">
          <h3>Log</h3>
          <pre class="detail__log">{{ detail.log }}</pre>
        </template>
      </div>
    </AppModal>
  </div>
</template>

<style scoped>
.page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.page__header h1 {
  font-size: 1.5rem;
}
.muted {
  color: var(--p-text-muted-color);
}
.stats {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}
.stat {
  background: var(--app-surface-card);
  border: 1px solid var(--app-surface-border);
  border-radius: 10px;
  padding: 10px 16px;
  display: flex;
  flex-direction: column;
  min-width: 130px;
}
.stat span {
  font-size: 1.4rem;
  font-weight: 700;
}
.stat small {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}
.stat--ok span {
  color: var(--p-green-500);
}
.stat--bad span {
  color: var(--p-red-500);
}
.stat--run span {
  color: var(--p-amber-500);
}
.section {
  font-size: 1.05rem;
  margin: 8px 0 12px;
}
.section small {
  font-weight: 400;
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
}
.tasks {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 12px;
  margin-bottom: 24px;
}
.task {
  background: var(--app-surface-card);
  border: 1px solid var(--app-surface-border);
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.task__name {
  font-weight: 700;
}
.task__desc {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
  margin-top: 4px;
}
.task__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.8rem;
  border-top: 1px solid var(--app-surface-border);
  padding-top: 8px;
}
.task__last {
  display: flex;
  align-items: center;
  gap: 6px;
}
.detail {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 0.9rem;
}
.detail h3 {
  font-size: 0.95rem;
  margin-top: 8px;
}
.detail__error {
  background: var(--p-surface-100);
  padding: 12px;
  border-radius: 8px;
  font-size: 0.78rem;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 380px;
  overflow: auto;
}
.detail__log {
  background: var(--p-surface-900);
  color: var(--p-surface-0);
  padding: 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 420px;
  overflow: auto;
}
</style>
