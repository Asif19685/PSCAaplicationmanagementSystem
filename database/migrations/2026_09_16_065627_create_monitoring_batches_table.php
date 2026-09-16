<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monitoring_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 64)->unique();
            $table->integer('total_websites')->default(0);
            $table->integer('successful')->default(0);
            $table->integer('failed')->default(0);
            $table->enum('trigger_type', ['manual', 'scheduler'])->default('manual');
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamps();

            $table->index('batch_id');
            $table->index('triggered_by');
            $table->index('trigger_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_batches');
    }
};
