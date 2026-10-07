import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api, ApiError, ensureCsrfCookie } from '@/core/services/api.service';
import type { ItemResponse, User } from '@/core/models';

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfilePayload {
  name?: string;
  email?: string;
}

/**
 * Session state. The source of truth is the server session (cookie); this store
 * only mirrors who the user is. `ensureLoaded()` asks the API once per page load
 * and the router guard waits for it, so routes never render with a stale guess.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const loaded = ref(false);
  let loading: Promise<void> | null = null;

  const isAuthenticated = computed(() => user.value !== null);
  const roles = computed<string[]>(() => user.value?.roles ?? []);
  const permissions = computed<string[]>(() => user.value?.permissions ?? []);

  async function fetchUser(): Promise<void> {
    try {
      const response = await api.get<ItemResponse<User>>('/auth/me');
      user.value = response.data.data;
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        user.value = null;
      } else {
        throw error;
      }
    } finally {
      loaded.value = true;
    }
  }

  function ensureLoaded(): Promise<void> {
    if (loaded.value) return Promise.resolve();
    loading ??= fetchUser().finally(() => {
      loading = null;
    });
    return loading;
  }

  async function login(credentials: LoginCredentials): Promise<void> {
    await ensureCsrfCookie();
    const response = await api.post<ItemResponse<User>>('/auth/login', credentials);
    user.value = response.data.data;
    loaded.value = true;
  }

  async function logout(): Promise<void> {
    try {
      await api.post('/auth/logout');
    } finally {
      clear();
    }
  }

  /** Forget the user locally (e.g. the API answered 401: the session expired). */
  function clear(): void {
    user.value = null;
    loaded.value = true;
  }

  async function updateProfile(payload: UpdateProfilePayload): Promise<void> {
    const response = await api.patch<ItemResponse<User>>('/auth/profile', payload);
    user.value = response.data.data;
  }

  async function changePassword(payload: ChangePasswordPayload): Promise<void> {
    await api.put('/auth/password', payload);
  }

  async function forgotPassword(email: string): Promise<string> {
    await ensureCsrfCookie();
    const response = await api.post<{ message: string }>('/auth/forgot-password', { email });
    return response.data.message;
  }

  async function resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<string> {
    await ensureCsrfCookie();
    const response = await api.post<{ message: string }>('/auth/reset-password', payload);
    return response.data.message;
  }

  return {
    user,
    loaded,
    isAuthenticated,
    roles,
    permissions,
    ensureLoaded,
    fetchUser,
    login,
    logout,
    clear,
    updateProfile,
    changePassword,
    forgotPassword,
    resetPassword,
  };
});
