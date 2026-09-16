<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id');
            $table->string('batch_id', 64)->nullable();

            // Status & Performance
            $table->enum('status', ['up', 'down', 'error', 'login_failed']);
            $table->integer('http_status_code')->nullable();
            $table->decimal('total_response_time_seconds', 8, 3)->nullable();

            // Logs & Screenshots
            $table->text('error_message')->nullable();
            $table->json('console_errors')->nullable();
            $table->text('screenshot_path')->nullable();

            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            $table->index('website_id');
            $table->index('batch_id');
            $table->index('status');
            $table->index('checked_at');
            $table->index(['website_id', 'status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_logs');
    }
};
