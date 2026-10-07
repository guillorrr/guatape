import { reactive, ref } from 'vue';
import type { UnwrapRef } from 'vue';
import { ApiError } from '@/core/services/api.service';

/**
 * Form state + server-side validation errors in one place.
 *
 *   const form = useForm({ name: '', email: '' })
 *   await form.submit(() => api.post('/users', form.data))
 *   form.error('email')   // first 422 message for the field
 *
 * `submit` rethrows the error so the caller can still toast or branch on it;
 * 422 field errors are already stored by then.
 */
export function useForm<T extends Record<string, unknown>>(initial: T) {
  const data = reactive({ ...initial }) as UnwrapRef<T>;
  const errors = ref<Record<string, string[]>>({});
  const processing = ref(false);

  function error(field: string): string | undefined {
    return errors.value[field]?.[0];
  }

  function hasError(field: string): boolean {
    return !!errors.value[field]?.length;
  }

  function clearErrors(): void {
    errors.value = {};
  }

  function reset(values: Partial<T> = {}): void {
    Object.assign(data as object, initial, values);
    clearErrors();
  }

  async function submit<R>(request: () => Promise<R>): Promise<R> {
    processing.value = true;
    clearErrors();
    try {
      return await request();
    } catch (e) {
      if (e instanceof ApiError && e.isValidation) {
        errors.value = e.errors;
      }
      throw e;
    } finally {
      processing.value = false;
    }
  }

  return { data, errors, processing, error, hasError, clearErrors, reset, submit };
}
