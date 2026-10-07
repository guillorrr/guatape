import { ApiError } from '@/core/services/api.service';
import { ref } from 'vue';

export function useFileUpload() {
  const uploading = ref(false);
  const progress = ref(0);
  const error = ref<string | null>(null);

  function createFormData(file: File, extraFields?: Record<string, string | number>): FormData {
    const formData = new FormData();
    formData.append('file', file);
    if (extraFields) {
      Object.entries(extraFields).forEach(([key, value]) => {
        formData.append(key, String(value));
      });
    }
    return formData;
  }

  async function upload<T>(
    uploadFn: (formData: FormData) => Promise<{ data: T }>,
    file: File,
    extraFields?: Record<string, string | number>,
  ): Promise<T | null> {
    uploading.value = true;
    progress.value = 0;
    error.value = null;

    try {
      const formData = createFormData(file, extraFields);
      const response = await uploadFn(formData);
      progress.value = 100;
      return response.data;
    } catch (e) {
      error.value =
        e instanceof ApiError ? (e.fieldError('file') ?? e.message) : 'Error al subir el archivo';
      return null;
    } finally {
      uploading.value = false;
    }
  }

  function reset() {
    uploading.value = false;
    progress.value = 0;
    error.value = null;
  }

  return { uploading, progress, error, upload, createFormData, reset };
}
