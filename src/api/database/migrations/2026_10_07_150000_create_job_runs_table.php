<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('job_uuid')->nullable()->index();
            $table->string('name')->index();
            $table->string('queue')->nullable();
            $table->string('domain')->default('Other')->index();
            $table->string('status', 20)->index();
            $table->string('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();
            $table->mediumText('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};
