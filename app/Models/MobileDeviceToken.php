<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MobileDeviceToken extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'platform',
        'device_id',
        'device_name',
        'app_version',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
