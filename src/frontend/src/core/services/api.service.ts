import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Request interceptor: attach auth token
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor: handle 401
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  },
);

export const api = {
  get<T>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return apiClient.get<T>(url, config);
  },
  post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return apiClient.post<T>(url, data, config);
  },
  put<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return apiClient.put<T>(url, data, config);
  },
  patch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return apiClient.patch<T>(url, data, config);
  },
  delete<T>(url: string, config?: AxiosRequestConfig): Promise<AxiosResponse<T>> {
    return apiClient.delete<T>(url, config);
  },
};

export default apiClient;
