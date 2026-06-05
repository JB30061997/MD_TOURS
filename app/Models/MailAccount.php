<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'username',
        'password',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    public function messages()
    {
        return $this->hasMany(MailMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
