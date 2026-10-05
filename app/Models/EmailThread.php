<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailThread extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unread' => 'boolean',
        'spam' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'thread_id');
    }

    public function latestOut()
    {
        // The direction filter must live inside ofMany(), otherwise the
        // aggregate picks the newest message overall and then filters it out.
        return $this->hasOne(EmailMessage::class, 'thread_id')
            ->ofMany(['sent_at' => 'max'], fn ($q) => $q->where('direction', 'out'));
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ThreadRead::class, 'thread_id');
    }
}
