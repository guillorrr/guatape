<script setup lang="ts" generic="T extends object">
/**
 * A list of sub-forms (phone numbers, schedule lines, contacts…). Each row is
 * rendered by the default slot; edit the row object in place with v-model on
 * its fields. 422 errors for row fields come as "items.2.phone": use
 * `form.error(\`items.${index}.phone\`)` inside the slot.
 *
 *   <AppRepeater v-model="form.data.phones" :new-item="() => ({ type: 'mobile', number: '' })">
 *     <template #default="{ item, index }">
 *       <InputText v-model="item.number" :invalid="form.hasError(`phones.${index}.number`)" />
 *     </template>
 *   </AppRepeater>
 */
import Button from 'primevue/button';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue: T[];
    newItem: () => T;
    min?: number;
    max?: number;
    addLabel?: string;
    /** Show move up/down buttons. */
    sortable?: boolean;
    emptyText?: string;
    disabled?: boolean;
  }>(),
  {
    min: 0,
    max: Infinity,
    addLabel: 'Agregar',
    sortable: false,
    emptyText: 'Sin elementos.',
    disabled: false,
  },
);

const emit = defineEmits<{ 'update:modelValue': [value: T[]] }>();

const canAdd = computed(() => !props.disabled && props.modelValue.length < props.max);
const canRemove = computed(() => !props.disabled && props.modelValue.length > props.min);

function add() {
  if (canAdd.value) emit('update:modelValue', [...props.modelValue, props.newItem()]);
}

function remove(index: number) {
  if (!canRemove.value) return;
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  );
}

function move(index: number, delta: -1 | 1) {
  const target = index + delta;
  if (target < 0 || target >= props.modelValue.length) return;
  const next = [...props.modelValue];
  [next[index], next[target]] = [next[target], next[index]];
  emit('update:modelValue', next);
}
</script>

<template>
  <div class="repeater">
    <p v-if="!modelValue.length" class="repeater__empty">{{ emptyText }}</p>
    <div v-for="(item, index) in modelValue" :key="index" class="repeater__row">
      <div class="repeater__body">
        <slot :item="item" :index="index" />
      </div>
      <div class="repeater__actions">
        <template v-if="sortable">
          <Button
            v-tooltip.top="'Subir'"
            icon="pi pi-arrow-up"
            text
            rounded
            size="small"
            severity="secondary"
            aria-label="Subir"
            :disabled="disabled || index === 0"
            @click="move(index, -1)"
          />
          <Button
            v-tooltip.top="'Bajar'"
            icon="pi pi-arrow-down"
            text
            rounded
            size="small"
            severity="secondary"
            aria-label="Bajar"
            :disabled="disabled || index === modelValue.length - 1"
            @click="move(index, 1)"
          />
        </template>
        <Button
          v-tooltip.top="'Quitar'"
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          aria-label="Quitar"
          :disabled="!canRemove"
          @click="remove(index)"
        />
      </div>
    </div>
    <div>
      <Button
        :label="addLabel"
        icon="pi pi-plus"
        size="small"
        outlined
        :disabled="!canAdd"
        @click="add"
      />
    </div>
  </div>
</template>

<style scoped>
.repeater {
  display: flex;
  flex-direction: column;
  gap: 8px;
  /* Rows adapt to the space the repeater gets, not to the viewport. */
  container-type: inline-size;
}

.repeater__empty {
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}

.repeater__row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  padding: 10px;
  border: 1px solid var(--app-surface-border);
  border-radius: 8px;
}

.repeater__body {
  flex: 1;
  min-width: 0;
}

.repeater__actions {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}

/* Narrow: actions go under the row's fields instead of squeezing them. */
@container (max-width: 480px) {
  .repeater__row {
    flex-direction: column;
    align-items: stretch;
  }

  .repeater__actions {
    justify-content: flex-end;
  }
}
</style>
