<?php

namespace Tests\Feature\Attachments;

use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['attachments.disk' => 'local']);
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::Admin->value);
        $this->target = User::factory()->create();
    }

    public function test_upload_list_download_and_delete(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post("/api/v1/attachments/users/{$this->target->id}", [
            'file' => UploadedFile::fake()->create('Contrato Firmado.pdf', 120, 'application/pdf'),
            'notes' => 'signed copy',
        ], ['Accept' => 'application/json'])->assertCreated();

        $attachment = Attachment::findOrFail($response->json('data.id'));
        $this->assertSame("attachments/user/{$this->target->id}/{$attachment->id}__contrato-firmado.pdf", $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($this->admin, 'web')->getJson("/api/v1/attachments/users/{$this->target->id}")
            ->assertOk()
            ->assertJsonPath('data.0.original_filename', 'Contrato Firmado.pdf');

        $this->actingAs($this->admin, 'web')->get("/api/v1/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertDownload('Contrato Firmado.pdf');

        $this->actingAs($this->admin, 'web')->deleteJson("/api/v1/attachments/{$attachment->id}")->assertNoContent();
        Storage::disk('local')->assertMissing($attachment->path);
        $this->assertModelMissing($attachment);
    }

    public function test_unknown_parent_type_is_404_and_permissions_apply(): void
    {
        $this->actingAs($this->admin, 'web')->getJson('/api/v1/attachments/orders/1')->assertNotFound();

        $member = User::factory()->create();
        $member->assignRole(UserRole::Member->value);
        $this->actingAs($member, 'web')->getJson("/api/v1/attachments/users/{$this->target->id}")->assertForbidden();
        $this->actingAs($member, 'web')->post("/api/v1/attachments/users/{$this->target->id}", [
            'file' => UploadedFile::fake()->create('x.txt', 1),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }
}
