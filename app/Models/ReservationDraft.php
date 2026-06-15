<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationDraft extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'mail_account_id',
        'mail_message_id',
        'planning_id',
        'status',
        'confidence',
        'source_from',
        'source_subject',
        'parsed_payload',
        'validation_notes',
        'validated_at',
    ];

    protected $casts = [
        'parsed_payload' => 'array',
        'validated_at' => 'datetime',
    ];

    public function mailAccount()
    {
        return $this->belongsTo(MailAccount::class);
    }

    public function mailMessage()
    {
        return $this->belongsTo(MailMessage::class);
    }

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }
}
