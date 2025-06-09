<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'device_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
