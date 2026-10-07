import type { AxiosAdapter, InternalAxiosRequestConfig } from 'axios';
import axios, { AxiosError } from 'axios';
import { afterEach, describe, expect, it, vi } from 'vitest';
import apiClient, { api, ApiError, onApiError } from '@/core/services/api.service';

function respond(status: number, data: unknown): AxiosAdapter {
  return (config) => {
    const response = {
      data,
      status,
      statusText: '',
      headers: {},
      config: config as InternalAxiosRequestConfig,
    };
    return status < 400
      ? Promise.resolve(response)
      : Promise.reject(
          new AxiosError(
            'fail',
            String(status),
            config as InternalAxiosRequestConfig,
            null,
            response,
          ),
        );
  };
}

describe('api.service', () => {
  const original = apiClient.defaults.adapter;
  afterEach(() => {
    apiClient.defaults.adapter = original;
    vi.restoreAllMocks();
  });

  it('turns a 422 into an ApiError with field errors', async () => {
    apiClient.defaults.adapter = respond(422, {
      message: 'Invalid',
      errors: { email: ['Taken.'] },
    });

    const error = await api.post('/users', {}).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.isValidation).toBe(true);
    expect(error.fieldError('email')).toBe('Taken.');
  });

  it('keeps the 409 code and details', async () => {
    apiClient.defaults.adapter = respond(409, {
      message: 'Dup',
      code: 'duplicate_unique_key',
      details: { column: 'email' },
    });

    const error = await api.post('/users', {}).catch((e) => e);

    expect(error.code).toBe('duplicate_unique_key');
    expect(error.details).toEqual({ column: 'email' });
  });

  it('refreshes the CSRF cookie and retries once on 419', async () => {
    const csrf = vi.spyOn(axios, 'get').mockResolvedValue({});
    let calls = 0;
    apiClient.defaults.adapter = (config) =>
      (++calls === 1 ? respond(419, { message: 'CSRF' }) : respond(200, { ok: true }))(config);

    const response = await api.post<{ ok: boolean }>('/auth/login', {});

    expect(response.data.ok).toBe(true);
    expect(calls).toBe(2);
    expect(csrf).toHaveBeenCalledWith('/sanctum/csrf-cookie', expect.anything());
  });

  it('notifies listeners of 401/403/5xx but not of 422', async () => {
    const seen: number[] = [];
    const off = onApiError((e) => seen.push(e.status));

    for (const status of [401, 403, 422, 500]) {
      apiClient.defaults.adapter = respond(status, { message: 'x' });
      await api.get('/x').catch(() => undefined);
    }
    off();

    expect(seen).toEqual([401, 403, 500]);
  });
});
