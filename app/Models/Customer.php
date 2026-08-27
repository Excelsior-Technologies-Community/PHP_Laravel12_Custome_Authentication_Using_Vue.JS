<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'email_verification_token',
        'two_factor_enabled',
        'two_factor_otp',
        'two_factor_otp_expires_at',
        'is_active',
        'deactivated_at',
        'avatar',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'two_factor_otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_otp_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
