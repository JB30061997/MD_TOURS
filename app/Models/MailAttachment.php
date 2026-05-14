<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAttachment extends Model
{
    protected $fillable = [
        'mail_message_id',
        'file_name',
        'mime_type',
        'size',
        'path',
    ];

    public function message()
    {
        return $this->belongsTo(MailMessage::class, 'mail_message_id');
    }
}