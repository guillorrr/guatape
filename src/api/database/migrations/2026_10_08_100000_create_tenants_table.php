<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants plus tenant_id on the scaffold's tenant-owned tables. The columns
 * are nullable: with tenancy off they stay null, and central records (super
 * admins, scheduler runs) are the ones with null when it's on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('status', 20)->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('locale');
        });

        foreach (['attachments', 'job_runs'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['key']);
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'key']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('key');
        });
        foreach (['attachments', 'job_runs'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('tenant_id'));
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('is_super_admin');
        });
        Schema::dropIfExists('tenants');
    }
};
