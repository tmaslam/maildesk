<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Team-wide custom label (MailDesk only — not written back to Gmail). */
class Label extends Model
{
    protected $fillable = ['name', 'color', 'created_by'];

    /** Gmail-like label colours. */
    public const COLORS = [
        '#d93025', '#e37400', '#f9ab00', '#188038', '#007b83',
        '#1a73e8', '#8430ce', '#b80672', '#795548', '#5f6368',
    ];

    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(EmailThread::class, 'email_thread_label', 'label_id', 'thread_id');
    }

    /** Sidebar list with unread counts, like Gmail. */
    public static function sidebar()
    {
        return static::withCount(['threads as unread_count' => fn ($q) => $q->where('unread', true)])
            ->orderBy('name')->get();
    }

    public static function nextColor(): string
    {
        return self::COLORS[static::count() % count(self::COLORS)];
    }
}
