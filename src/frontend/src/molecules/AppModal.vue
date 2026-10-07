<script setup lang="ts">
import Dialog from 'primevue/dialog';
import { computed } from 'vue';

const props = defineProps<{
  title: string;
  show: boolean;
  size?: 'sm' | 'md' | 'lg';
}>();

const emit = defineEmits<{
  close: [];
}>();

const width = computed(() => {
  const map: Record<string, string> = { sm: '400px', md: '600px', lg: '900px' };
  return map[props.size ?? 'md'];
});

const visible = computed({
  get: () => props.show,
  set: (val) => {
    if (!val) emit('close');
  },
});
</script>

<template>
  <Dialog
    v-model:visible="visible"
    :header="title"
    modal
    :style="{ width }"
    :breakpoints="{ '640px': '95vw' }"
    :draggable="false"
  >
    <slot />
    <template v-if="$slots.footer" #footer>
      <slot name="footer" />
    </template>
  </Dialog>
</template>
