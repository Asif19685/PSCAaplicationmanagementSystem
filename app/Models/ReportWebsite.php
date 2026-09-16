<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportWebsite extends Model
{
    use HasFactory;

    protected $table = 'report_websites';

    protected $fillable = [
        'report_id',
        'website_id',
        'total_checks',
        'uptime_count',
        'downtime_count',
        'uptime_percentage',
        'avg_response_time_seconds',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'website_id' => 'integer',
        'total_checks' => 'integer',
        'uptime_count' => 'integer',
        'downtime_count' => 'integer',
        'uptime_percentage' => 'float',
        'avg_response_time_seconds' => 'float',
    ];

    /**
     * Relationship: Belongs to Report
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * Relationship: Belongs to Website
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id');
    }
}
