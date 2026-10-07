import { describe, expect, it } from 'vitest';
import { reactive } from 'vue';
import { useForm } from '@/composables/useForm';
import { ApiError } from '@/core/services/api.service';

describe('useForm', () => {
  it('stores 422 field errors and rethrows', async () => {
    const form = useForm({ email: '' });

    await expect(
      form.submit(() => Promise.reject(new ApiError('Invalid', 422, { email: ['Required.'] }))),
    ).rejects.toBeInstanceOf(ApiError);

    expect(form.error('email')).toBe('Required.');
    expect(form.hasError('email')).toBe(true);
    expect(form.processing.value).toBe(false);
  });

  it('keeps errors empty for non-validation failures', async () => {
    const form = useForm({ email: '' });

    await expect(form.submit(() => Promise.reject(new ApiError('Boom', 500)))).rejects.toThrow(
      'Boom',
    );

    expect(form.errors.value).toEqual({});
  });

  it('clears previous errors on a new submit and returns the result', async () => {
    const form = useForm({ email: '' });
    form.errors.value = { email: ['old'] };

    const result = await form.submit(() => Promise.resolve('ok'));

    expect(result).toBe('ok');
    expect(form.errors.value).toEqual({});
  });

  it('reset restores the initial values plus overrides', () => {
    const form = useForm({ name: 'a', roles: [] as string[] });
    form.data.name = 'changed';
    form.data.roles.push('admin');

    form.reset({ name: 'b' });

    expect(form.data.name).toBe('b');
    expect(form.data.roles).toEqual([]);
  });

  it('accepts reactive values (e.g. from a store) without sharing them', () => {
    const user = reactive({ name: 'Ana', roles: ['admin'] });
    const form = useForm({ name: '', roles: [] as string[] });

    form.reset({ name: user.name, roles: user.roles });
    form.data.roles.push('member');

    expect(user.roles).toEqual(['admin']);
  });
});
