import { api } from './api.service';

export interface Attachment {
  id: number;
  original_filename: string;
  mime_type: string | null;
  size_bytes: number;
  notes: string | null;
  uploaded_by?: { id: number; name: string } | null;
  created_at: string;
  download_url: string;
}

/** `type` is the key in the API's config/attachments.php `parents` (e.g. "users"). */
export const attachmentService = {
  list: (type: string, id: number) => api.get<{ data: Attachment[] }>(`/attachments/${type}/${id}`),
  upload: (
    type: string,
    id: number,
    file: File,
    notes?: string,
    onProgress?: (percent: number) => void,
  ) => {
    const body = new FormData();
    body.append('file', file);
    if (notes) body.append('notes', notes);
    return api.post<{ data: Attachment }>(`/attachments/${type}/${id}`, body, {
      onUploadProgress: (e) => onProgress?.(e.total ? Math.round((e.loaded * 100) / e.total) : 0),
    });
  },
  destroy: (id: number) => api.delete<null>(`/attachments/${id}`),
};
