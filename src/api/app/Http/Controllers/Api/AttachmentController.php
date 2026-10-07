<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Concerns\HasAttachments;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attachments of any model listed in config/attachments.php `parents`, each
 * with its own manage/view permission.
 */
class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function index(Request $request, string $type, int $id): AnonymousResourceCollection
    {
        $parent = $this->parent($request, $type, $id, 'view');

        return AttachmentResource::collection($parent->attachments()->with('uploadedBy')->get());
    }

    public function store(StoreAttachmentRequest $request, string $type, int $id): JsonResponse
    {
        $parent = $this->parent($request, $type, $id, 'manage');

        $attachment = DB::transaction(fn () => $this->attachments->store(
            $request->file('file'),
            $parent,
            $request->validated('notes'),
            $request->user()->id,
        ));

        return (new AttachmentResource($attachment->load('uploadedBy')))->response()->setStatusCode(201);
    }

    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorizeFor($request, $attachment, 'view');
        abort_unless($attachment->existsOnDisk(), 404, __('The file is no longer on disk.'));

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_filename);
    }

    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        $this->authorizeFor($request, $attachment, 'manage');
        $this->attachments->delete($attachment);

        return response()->json(null, 204);
    }

    /**
     * @param  'manage'|'view'  $ability
     * @return Model&HasAttachments
     */
    private function parent(Request $request, string $type, int $id, string $ability): Model
    {
        $config = config("attachments.parents.{$type}");
        abort_unless(is_array($config), 404);
        abort_unless($request->user()->can($config[$ability]), 403);

        return $config['model']::findOrFail($id);
    }

    /**
     * @param  'manage'|'view'  $ability
     */
    private function authorizeFor(Request $request, Attachment $attachment, string $ability): void
    {
        $config = collect(config('attachments.parents'))->firstWhere('model', $attachment->attachable_type);
        abort_unless(is_array($config) && $request->user()->can($config[$ability]), 403);
    }
}
