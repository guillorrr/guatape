<script setup lang="ts">
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { onApiError } from '@/core/services/api.service';
import { useAuthStore } from '@/core/stores/auth.store';
import { useAppToast } from '@/composables/useAppToast';

const router = useRouter();
const auth = useAuthStore();
const toast = useAppToast();

// Errors no page handles itself: an expired session sends the user to login,
// a forbidden action or a server/network failure gets a toast.
const unsubscribe = onApiError((error) => {
  if (error.status === 401) {
    if (auth.isAuthenticated) {
      auth.clear();
      toast.warn('Tu sesión expiró. Volvé a iniciar sesión.');
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    }
    return;
  }
  toast.error(error.status === 403 ? 'No tenés permiso para hacer esto.' : error.message);
});
onBeforeUnmount(unsubscribe);
</script>

<template>
  <RouterView />
  <Toast position="top-right" />
  <ConfirmDialog />
</template>
