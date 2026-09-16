<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    use HasFactory;

    protected $table = 'monitoring_logs';

    protected $fillable = [
        'website_id',
        'batch_id',
        'status',
        'http_status_code',
        'total_response_time_seconds',
        'error_message',
        'console_errors',
        'screenshot_path',
        'checked_at',
    ];

    protected $casts = [
        'website_id' => 'integer',
        'http_status_code' => 'integer',
        'total_response_time_seconds' => 'float',
        'console_errors' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Relationship: Belongs to Website
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id');
    }

    /**
     * Relationship: Belongs to MonitoringBatch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(MonitoringBatch::class, 'batch_id', 'batch_id');
    }

    /**
     * Helper to get public screenshot URL
     */
    public function getScreenshotUrlAttribute(): ?string
    {
        if (empty($this->screenshot_path)) {
            return null;
        }

        if (str_starts_with($this->screenshot_path, 'http://') || str_starts_with($this->screenshot_path, 'https://')) {
            return $this->screenshot_path;
        }

        return asset('storage/' . ltrim($this->screenshot_path, '/'));
    }
}
