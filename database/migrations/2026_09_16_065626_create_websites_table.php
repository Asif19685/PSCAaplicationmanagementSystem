<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('url');

            // Login Testing Configuration
            $table->boolean('requires_login')->default(false);
            $table->text('login_url')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('username_field', 150)->nullable();
            $table->string('password_field', 150)->nullable();
            $table->string('submit_button', 150)->nullable();
            $table->string('expected_text', 255)->nullable();

            // Status & Audit
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('is_active');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
