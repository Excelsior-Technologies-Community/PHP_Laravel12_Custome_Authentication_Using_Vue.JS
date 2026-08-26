<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSession extends Model
{
    protected $table = 'customer_sessions';

    protected $fillable = [
        'customer_id',
        'session_token',
        'device_name',
        'browser',
        'platform',
        'ip_address',
        'user_agent',
        'login_at',
        'last_activity_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'session_token',
        'user_agent',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}