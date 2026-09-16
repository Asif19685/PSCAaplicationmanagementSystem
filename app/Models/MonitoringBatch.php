<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringBatch extends Model
{
    use HasFactory;

    protected $table = 'monitoring_batches';

    protected $fillable = [
        'batch_id',
        'total_websites',
        'successful',
        'failed',
        'trigger_type',
        'triggered_by',
    ];

    protected $casts = [
        'total_websites' => 'integer',
        'successful' => 'integer',
        'failed' => 'integer',
        'triggered_by' => 'integer',
    ];

    /**
     * Relationship: Has many MonitoringLogs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class, 'batch_id', 'batch_id');
    }

    /**
     * Relationship: Triggered by User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
