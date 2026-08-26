<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'email_verified_at',
        'avatar',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'customer_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'customer_id');
    }
}