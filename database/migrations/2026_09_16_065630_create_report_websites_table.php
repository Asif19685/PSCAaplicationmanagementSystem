<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_websites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('website_id');

            // Individual Website Metrics
            $table->integer('total_checks')->default(0);
            $table->integer('uptime_count')->default(0);
            $table->integer('downtime_count')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(0.00);
            $table->decimal('avg_response_time_seconds', 8, 3)->default(0.000);

            $table->timestamps();

            $table->index('report_id');
            $table->index('website_id');
            $table->unique(['report_id', 'website_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_websites');
    }
};
