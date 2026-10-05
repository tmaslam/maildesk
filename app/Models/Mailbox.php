<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mailbox extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'watch_expires_at' => 'datetime',
    ];

    public function threads(): HasMany
    {
        return $this->hasMany(EmailThread::class);
    }

    public function isConnected(): bool
    {
        return !empty($this->refresh_token);
    }

    /** Brands with unread counts, as the sidebar shows them on every page. */
    public static function sidebar()
    {
        return static::withCount(['threads as unread_count' => fn ($q) => $q->where('unread', true)])
            ->orderBy('sort_order')->orderBy('brand_name')->get();
    }
}
