import axios from 'axios';
import type { AxiosError, AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import { currentLocale, i18n } from '@/i18n';

/**
 * HTTP client for the Laravel API.
 *
 * Auth is Sanctum's cookie-based SPA mode: the browser holds the session cookie
 * (HttpOnly) and the XSRF-TOKEN cookie; axios echoes the latter back as the
 * X-XSRF-TOKEN header. Nothing auth-related lives in localStorage.
 *
 * Every failed request is normalized into an ApiError, so pages handle one
 * shape: status, message, field errors (422) and an optional machine code (409).
 */
const apiClient: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly errors: Record<string, string[]> = {},
    public readonly code: string | null = null,
    public readonly details: unknown = null,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get isValidation(): boolean {
    return this.status === 422;
  }

  /** First message for a field (422), handy for <small> hints under inputs. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

type ApiErrorListener = (error: ApiError) => void;
const listeners = new Set<ApiErrorListener>();

/**
 * Subscribe to errors no page is expected to handle itself (401, 403, 5xx,
 * network). The shell uses it to show a toast or redirect to login.
 */
export function onApiError(listener: ApiErrorListener): () => void {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

/** Primes the XSRF-TOKEN cookie. Call before the first state-changing request (login). */
export async function ensureCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

function toApiError(
  error: AxiosError<{
    message?: string;
    errors?: Record<string, string[]>;
    code?: string;
    details?: unknown;
  }>,
): ApiError {
  if (!error.response) {
    return new ApiError(i18n.global.t('errors.network'), 0);
  }
  const { status, data } = error.response;
  const fallback = i18n.global.t(status >= 500 ? 'errors.server' : 'errors.requestFailed');
  return new ApiError(
    data?.message || fallback,
    status,
    data?.errors ?? {},
    data?.code ?? null,
    data?.details ?? null,
  );
}

// The API answers in the UI's language (SetLocale) unless the user saved one.
apiClient.interceptors.request.use((config) => {
  config.headers.set('Accept-Language', currentLocale());
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as (AxiosRequestConfig & { _csrfRetried?: boolean }) | undefined;

    // 419 = CSRF token mismatch (session expired or cookie never primed):
    // refresh the cookie and retry once.
    if (error.response?.status === 419 && config && !config._csrfRetried) {
      config._csrfRetried = true;
      await ensureCsrfCookie();
      return apiClient.request(config);
    }

    const apiError = toApiError(error as AxiosError<never>);
    if (
      apiError.status === 401 ||
      apiError.status === 403 ||
      apiError.status === 0 ||
      apiError.status >= 500
    ) {
      listeners.forEach((listener) => listener(apiError));
    }
    return Promise.reject(apiError);
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
