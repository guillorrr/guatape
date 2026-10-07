<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'notes' => $this->notes,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => ['id' => $this->uploadedBy?->id, 'name' => $this->uploadedBy?->name]),
            'created_at' => $this->created_at,
            'download_url' => url("/api/v1/attachments/{$this->id}/download"),
        ];
    }
}
