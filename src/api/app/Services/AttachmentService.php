<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttachmentService
{
    /**
     * Stores the file and creates its row.
     *
     * The stored name is "<id>__<slug-of-original>.<ext>": readable when
     * browsing the disk, and two uploads with the same name never collide. The
     * row is created first (to get the id) and the binary written after; run it
     * inside a transaction so a failed write leaves no orphan row.
     *
     * @param  Model&HasAttachments  $attachable
     */
    public function store(UploadedFile $file, Model $attachable, ?string $notes = null, ?int $userId = null): Attachment
    {
        $disk = config('attachments.disk') ?: config('filesystems.default');
        $original = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $stem = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'file';

        $attachment = $attachable->attachments()->create([
            'disk' => $disk,
            'path' => '',
            'original_filename' => $original,
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'notes' => $notes,
            'uploaded_by' => $userId,
        ]);

        $name = $attachment->id.'__'.$stem.($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($attachable->attachmentDirectory(), $name, ['disk' => $disk]);
        if ($path === false) {
            throw new RuntimeException("Could not write the attachment to disk [{$disk}].");
        }

        $attachment->update(['path' => $path]);

        return $attachment;
    }

    /**
     * Deletes the row and its binary. A binary already gone from the disk is
     * not an error: keeping the row would point at nothing.
     */
    public function delete(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }
}
