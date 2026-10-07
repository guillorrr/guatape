<script setup lang="ts">
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { onApiError } from '@/core/services/api.service';
import { useAuthStore } from '@/core/stores/auth.store';
import { useAppToast } from '@/composables/useAppToast';
import { useI18n } from 'vue-i18n';

const router = useRouter();
const auth = useAuthStore();
const toast = useAppToast();
const { t, locale } = useI18n();

// Errors no page handles itself: an expired session sends the user to login,
// a forbidden action or a server/network failure gets a toast.
const unsubscribe = onApiError((error) => {
  // An unknown or suspended organization has its own page (TenantUnavailablePage).
  if (error.code === 'tenant_not_found' || error.code === 'tenant_suspended') return;
  if (error.status === 401) {
    if (auth.isAuthenticated) {
      auth.clear();
      toast.warn(t('errors.sessionExpired'));
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    }
    return;
  }
  toast.error(error.status === 403 ? t('errors.forbidden') : error.message);
});
onBeforeUnmount(unsubscribe);
</script>

<template>
  <!-- Remount on language change: some PrimeVue components read their texts
       (e.g. Password's prompt) only once, when they mount. -->
  <RouterView :key="locale" />
  <Toast position="top-right" />
  <ConfirmDialog />
</template>
