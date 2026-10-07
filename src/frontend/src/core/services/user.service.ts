import { api } from '@/core/services/api.service';
import type { ItemResponse, PaginatedResponse, Role, User } from '@/core/models';
import type { RemoteOption } from '@/molecules/AppRemoteSelect.vue';

export interface UserPayload {
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
  roles?: string[];
  locale?: string | null;
}

export const userService = {
  list(params: Record<string, string | number>) {
    return api.get<PaginatedResponse<User>>('/users', { params });
  },
  show(id: number) {
    return api.get<ItemResponse<User>>(`/users/${id}`);
  },
  create(payload: UserPayload) {
    return api.post<ItemResponse<User>>('/users', payload);
  },
  update(id: number, payload: Partial<UserPayload>) {
    return api.patch<ItemResponse<User>>(`/users/${id}`, payload);
  },
  destroy(id: number) {
    return api.delete<{ message: string }>(`/users/${id}`);
  },
  syncRoles(id: number, roles: string[]) {
    return api.put<ItemResponse<User>>(`/users/${id}/roles`, { roles });
  },
  setPassword(id: number, password: string, password_confirmation: string) {
    return api.put<{ message: string }>(`/users/${id}/password`, {
      password,
      password_confirmation,
    });
  },
  /** Options for AppRemoteSelect: first 20 matches of `search`, optionally by role. */
  async options(search: string, params: Record<string, unknown> = {}): Promise<RemoteOption[]> {
    const { data } = await api.get<PaginatedResponse<User>>('/users', {
      params: { search, per_page: 20, sort_by: 'name', ...params },
    });
    return data.data.map((u) => ({ value: u.id, label: u.name, description: u.email }));
  },
  roles() {
    return api.get<{ data: Role[] }>('/roles');
  },
};
