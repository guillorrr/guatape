import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { ApiError } from '@/core/services/api.service';
import { tenancyService, type TenancyContext } from '@/core/services/tenancy.service';

/**
 * Which organization this SPA is serving (from the host, resolved by the API).
 * Loaded once before the first route; with tenancy off it's {enabled: false}
 * and nothing in the UI changes.
 */
export const useTenancyStore = defineStore('tenancy', () => {
  const context = ref<TenancyContext>({ enabled: false, central: false, tenant: null });
  /** The host names an organization that doesn't exist or is suspended. */
  const unavailable = ref<'not_found' | 'suspended' | null>(null);
  const loaded = ref(false);
  let loading: Promise<void> | null = null;

  const tenant = computed(() => context.value.tenant);
  const isCentral = computed(() => context.value.enabled && context.value.central);

  async function load(): Promise<void> {
    try {
      context.value = (await tenancyService.context()).data.data;
    } catch (e) {
      if (
        e instanceof ApiError &&
        (e.code === 'tenant_not_found' || e.code === 'tenant_suspended')
      ) {
        unavailable.value = e.code === 'tenant_not_found' ? 'not_found' : 'suspended';
      }
    } finally {
      loaded.value = true;
    }
  }

  function ensureLoaded(): Promise<void> {
    if (loaded.value) return Promise.resolve();
    loading ??= load().finally(() => {
      loading = null;
    });
    return loading;
  }

  return { context, tenant, isCentral, unavailable, loaded, ensureLoaded };
});
