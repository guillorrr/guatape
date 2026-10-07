import { api } from './api.service';
import type { ItemResponse, PaginatedResponse } from '@/core/models';

export interface TenancyContext {
  enabled: boolean;
  /** Tenancy on and no organization: the central domain (platform admin). */
  central: boolean;
  tenant: { id: number; name: string; slug: string } | null;
}

export interface Tenant {
  id: number;
  name: string;
  slug: string;
  domain: string | null;
  status: 'active' | 'suspended';
  url: string;
  users_count?: number;
  created_at: string;
}

export interface TenantPayload {
  name: string;
  slug: string;
  domain?: string | null;
  admin: { name: string; email: string; password: string; password_confirmation: string };
}

export const tenancyService = {
  context: () => api.get<ItemResponse<TenancyContext>>('/tenancy'),
  list: (params: Record<string, string | number>) =>
    api.get<PaginatedResponse<Tenant>>('/tenants', { params }),
  create: (payload: TenantPayload) => api.post<ItemResponse<Tenant>>('/tenants', payload),
  update: (id: number, payload: Partial<Pick<Tenant, 'name' | 'domain' | 'status'>>) =>
    api.patch<ItemResponse<Tenant>>(`/tenants/${id}`, payload),
};
