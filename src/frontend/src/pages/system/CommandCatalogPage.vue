<script setup lang="ts">
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { activityService, type CatalogCommand } from '@/core/services/activity.service';
import { useAppToast } from '@/composables/useAppToast';
import { useFormatters } from '@/composables/useFormatters';
import { useI18n } from 'vue-i18n';
import { computed, onMounted, ref } from 'vue';

const toast = useAppToast();
const { formatDateTime } = useFormatters();
const { t } = useI18n();
const loading = ref(false);
const commands = ref<CatalogCommand[]>([]);
const query = ref('');
// Los históricos se esconden por defecto: ya corrieron y no queda nada que
// procesar, así que en la lista del día a día sólo serían ruido.
const showHistoric = ref(false);
const openName = ref<string | null>(null);

async function load() {
  loading.value = true;
  try {
    commands.value = (await activityService.commands()).data.data;
  } catch {
    toast.error(t('commands.loadFailed'));
  } finally {
    loading.value = false;
  }
}

// Busca en nombre, descripción y opciones: el nombre casi nunca se recuerda, y
// lo que uno tiene en la cabeza es lo que el comando HACE.
const historicCount = computed(
  () => commands.value.filter((c) => c.lifecycle === 'one_shot').length,
);

const filtered = computed(() => {
  const visible = showHistoric.value
    ? commands.value
    : commands.value.filter((c) => c.lifecycle !== 'one_shot');
  const q = query.value.trim().toLowerCase();
  if (!q) return visible;
  return visible.filter((c) =>
    [c.name, c.description, c.domain, ...c.options.map((o) => `${o.name} ${o.description}`)]
      .join(' ')
      .toLowerCase()
      .includes(q),
  );
});

const grouped = computed(() => {
  const map = new Map<string, CatalogCommand[]>();
  for (const c of filtered.value) {
    if (!map.has(c.domain)) map.set(c.domain, []);
    map.get(c.domain)!.push(c);
  }
  return [...map.entries()];
});

function toggle(name: string) {
  openName.value = openName.value === name ? null : name;
}

/** The full line to paste in the host terminal, not just the artisan part. */
function fullLine(command: CatalogCommand): string {
  return `docker compose exec -T api ${command.example}`;
}

async function copy(command: CatalogCommand) {
  try {
    await navigator.clipboard.writeText(fullLine(command));
    toast.success(t('commands.copied'));
  } catch {
    toast.error(t('commands.copyFailed'));
  }
}

onMounted(load);
</script>

<template>
  <div class="page-header">
    <h1>{{ t('commands.title') }}</h1>
    <span v-if="!loading" class="count">{{ t('commands.count', { n: commands.length }) }}</span>
  </div>

  <p class="intro">{{ t('commands.intro') }}</p>

  <div class="controls">
    <InputText v-model="query" :placeholder="t('commands.searchPlaceholder')" class="search" />
    <label v-if="historicCount" class="toggle">
      <input v-model="showHistoric" type="checkbox" />
      {{ t('commands.showHistoric', { n: historicCount }) }}
    </label>
  </div>

  <div v-if="loading" class="muted">{{ t('common.loading') }}</div>

  <div v-else-if="!filtered.length" class="muted">
    {{ t('commands.noMatch', { query }) }}
  </div>

  <div v-for="[domain, items] in grouped" v-else :key="domain" class="domain">
    <h2>
      {{ domain }} <span class="domain-count">{{ items.length }}</span>
    </h2>

    <div v-for="command in items" :key="command.name" class="command">
      <button class="command-head" type="button" @click="toggle(command.name)">
        <div class="command-title">
          <code>{{ command.name }}</code>
          <span v-if="command.lifecycle !== 'recurring'" class="badge" :class="command.lifecycle">{{
            t(`commands.lifecycle.${command.lifecycle}`)
          }}</span>
          <span
            v-if="command.schedule"
            class="badge"
            :title="t('commands.scheduled', { expression: command.schedule.expression })"
          >
            {{ command.schedule.expression }}
          </span>
        </div>
        <p class="command-desc">{{ command.description }}</p>
      </button>

      <div v-if="openName === command.name" class="command-body">
        <p v-if="command.note" class="note">{{ command.note }}</p>
        <div v-if="command.arguments.length" class="params">
          <h4>{{ t('commands.arguments') }}</h4>
          <div v-for="arg in command.arguments" :key="arg.name" class="param">
            <code>{{ arg.name }}</code>
            <span v-if="arg.required" class="req">{{ t('commands.required') }}</span>
            <span class="param-desc">{{ arg.description || '—' }}</span>
          </div>
        </div>

        <div v-if="command.options.length" class="params">
          <h4>{{ t('commands.options') }}</h4>
          <div v-for="opt in command.options" :key="opt.name" class="param">
            <code>--{{ opt.name }}{{ opt.accepts_value ? '=' : '' }}</code>
            <span v-if="opt.default !== null && opt.default !== ''" class="def">
              {{ t('commands.default', { value: String(opt.default) }) }}
            </span>
            <span class="param-desc">{{ opt.description || '—' }}</span>
          </div>
        </div>

        <p v-if="!command.arguments.length && !command.options.length" class="muted small">
          {{ t('commands.noParams') }}
        </p>

        <div class="run">
          <code class="line">{{ fullLine(command) }}</code>
          <Button
            :label="t('common.copy')"
            icon="pi pi-copy"
            size="small"
            text
            @click="copy(command)"
          />
        </div>

        <p v-if="command.schedule?.last_run" class="small muted">
          {{
            t('commands.lastRun', {
              date: formatDateTime(command.schedule.last_run.finished_at),
              status: t(`activity.status.${command.schedule.last_run.status}`),
            })
          }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 8px;
}
.page-header h1 {
  margin: 0;
}
.count {
  color: var(--text-color-secondary, #6b7280);
  font-size: 0.85rem;
}
.intro {
  margin: 0 0 16px;
  color: var(--text-color-secondary, #6b7280);
  font-size: 0.85rem;
  max-width: 70ch;
}
.search {
  width: 100%;
  max-width: 480px;
}
.controls {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}
.toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--text-color-secondary, #6b7280);
  cursor: pointer;
}
.note {
  margin: 12px 0 0;
  font-size: 0.8rem;
  color: var(--text-color-secondary, #6b7280);
  background: var(--surface-ground, #f9fafb);
  border-left: 3px solid var(--surface-border, #e5e7eb);
  padding: 8px 12px;
  border-radius: 0 6px 6px 0;
}
.muted {
  color: var(--text-color-secondary, #6b7280);
}
.small {
  font-size: 0.78rem;
}
.domain {
  margin-bottom: 28px;
}
.domain h2 {
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-color-secondary, #6b7280);
  margin: 0 0 10px;
}
.domain-count {
  font-weight: 400;
  opacity: 0.6;
}
.command {
  background: var(--surface-card, #fff);
  border: 1px solid var(--surface-border, #e5e7eb);
  border-radius: 10px;
  margin-bottom: 8px;
  overflow: hidden;
}
.command-head {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: 0;
  padding: 12px 16px;
  cursor: pointer;
  color: inherit;
  font: inherit;
}
.command-head:hover {
  background: var(--surface-hover, #f9fafb);
}
.command-title {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.command-title code {
  font-weight: 600;
  font-size: 0.9rem;
}
.badge.repair {
  background: #fffbeb;
  color: #b45309;
}
.badge.one_shot {
  background: #f3f4f6;
  color: #6b7280;
}
.badge {
  font-size: 0.7rem;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  padding: 2px 8px;
}
.command-desc {
  margin: 4px 0 0;
  font-size: 0.82rem;
  color: var(--text-color-secondary, #6b7280);
}
.command-body {
  padding: 4px 16px 16px;
  border-top: 1px solid var(--surface-border, #f3f4f6);
}
.params {
  margin: 12px 0;
}
.params h4 {
  margin: 0 0 6px;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-color-secondary, #9ca3af);
}
.param {
  display: flex;
  gap: 8px;
  align-items: baseline;
  flex-wrap: wrap;
  padding: 3px 0;
  font-size: 0.82rem;
}
.param code {
  background: var(--surface-ground, #f3f4f6);
  border-radius: 4px;
  padding: 1px 6px;
}
.req {
  font-size: 0.7rem;
  color: #b45309;
}
.def {
  font-size: 0.7rem;
  color: var(--text-color-secondary, #9ca3af);
}
.param-desc {
  color: var(--text-color-secondary, #6b7280);
}
.run {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 12px;
}
.line {
  background: #111827;
  color: #e5e7eb;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 0.8rem;
  overflow-x: auto;
  flex: 1;
  min-width: 0;
}
</style>
