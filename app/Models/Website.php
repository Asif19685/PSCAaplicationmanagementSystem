<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class Website extends Model
{
    use HasFactory;

    protected $table = 'websites';

    protected $fillable = [
        'name',
        'url',
        'requires_login',
        'login_url',
        'username',
        'password',
        'username_field',
        'password_field',
        'submit_button',
        'expected_text',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'requires_login' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    /**
     * Encrypt password on saving if provided
     */
    public function setPasswordAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['password'] = null;
        }
    }

    /**
     * Decrypt password on reading
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        if (empty($this->attributes['password'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['password']);
        } catch (\Throwable $e) {
            return $this->attributes['password'];
        }
    }

    /**
     * Encrypt username on saving if provided
     */
    public function setUsernameAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['username'] = Crypt::encryptString($value);
        } else {
            $this->attributes['username'] = null;
        }
    }

    /**
     * Decrypt username on reading
     */
    public function getDecryptedUsernameAttribute(): ?string
    {
        if (empty($this->attributes['username'])) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['username']);
        } catch (\Throwable $e) {
            return $this->attributes['username'];
        }
    }

    /**
     * Relationship: Has many MonitoringLogs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class, 'website_id')->orderBy('checked_at', 'desc');
    }

    /**
     * Relationship: Latest monitoring log
     */
    public function latestLog(): HasOne
    {
        return $this->hasOne(MonitoringLog::class, 'website_id')->latestOfMany('checked_at');
    }

    /**
     * Relationship: Has many ReportWebsite entries
     */
    public function reportWebsites(): HasMany
    {
        return $this->hasMany(ReportWebsite::class, 'website_id');
    }

    /**
     * Relationship: Created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
