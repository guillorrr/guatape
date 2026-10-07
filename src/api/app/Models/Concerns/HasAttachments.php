<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Gives a model attachments and decides where their files go. Register the
 * model in config/attachments.php `parents` to expose it through the API.
 */
trait HasAttachments
{
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }

    /**
     * Directory (relative to the disk) for this record's files. Override it for
     * a layout that means something to someone browsing the disk by hand.
     */
    public function attachmentDirectory(): string
    {
        return 'attachments/'.Str::kebab(class_basename($this)).'/'.$this->getKey();
    }
}
