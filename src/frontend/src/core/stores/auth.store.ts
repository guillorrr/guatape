import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/core/services/api.service';
import { useRouter } from 'vue-router';

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter();
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('auth_token'));
  const loading = ref(false);

  const isAuthenticated = computed(() => !!token.value);

  async function login(credentials: LoginCredentials) {
    loading.value = true;
    try {
      const response = await api.post<{ user: User; token: string }>('/auth/login', credentials);
      user.value = response.data.user;
      token.value = response.data.token;
      localStorage.setItem('auth_token', response.data.token);
      await router.push({ name: 'dashboard' });
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout');
    } finally {
      user.value = null;
      token.value = null;
      localStorage.removeItem('auth_token');
      await router.push({ name: 'login' });
    }
  }

  async function fetchUser() {
    if (!token.value) return;
    try {
      const response = await api.get<User>('/auth/user');
      user.value = response.data;
    } catch {
      await logout();
    }
  }

  return { user, token, loading, isAuthenticated, login, logout, fetchUser };
});
