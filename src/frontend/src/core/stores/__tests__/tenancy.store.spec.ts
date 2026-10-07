import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/core/services/api.service';
import { tenancyService } from '@/core/services/tenancy.service';
import { useTenancyStore } from '@/core/stores/tenancy.store';

describe('tenancy store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.restoreAllMocks();
  });

  it('loads the context once', async () => {
    const context = vi.spyOn(tenancyService, 'context').mockResolvedValue({
      data: {
        data: { enabled: true, central: false, tenant: { id: 1, name: 'Acme', slug: 'acme' } },
      },
    } as never);
    const store = useTenancyStore();

    await Promise.all([store.ensureLoaded(), store.ensureLoaded()]);

    expect(context).toHaveBeenCalledTimes(1);
    expect(store.tenant?.name).toBe('Acme');
    expect(store.isCentral).toBe(false);
  });

  it.each([
    ['tenant_not_found', 'not_found'],
    ['tenant_suspended', 'suspended'],
  ])('maps %s to an unavailable organization', async (code, expected) => {
    vi.spyOn(tenancyService, 'context').mockRejectedValue(new ApiError('x', 404, {}, code));
    const store = useTenancyStore();

    await store.ensureLoaded();

    expect(store.unavailable).toBe(expected);
  });

  it('with tenancy off nothing is central or unavailable', async () => {
    vi.spyOn(tenancyService, 'context').mockResolvedValue({
      data: { data: { enabled: false, central: false, tenant: null } },
    } as never);
    const store = useTenancyStore();

    await store.ensureLoaded();

    expect(store.isCentral).toBe(false);
    expect(store.unavailable).toBeNull();
  });
});
