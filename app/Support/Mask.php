<?php

namespace App\Support;

use App\Models\EmailThread;

/**
 * Privacy layer: agents must never see a customer's email address —
 * not in the thread list, not in the message body, nowhere.
 */
class Mask
{
    public static function isAdmin(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /** Redact every email address inside free text for non-admins. */
    public static function body(?string $text): string
    {
        $text = (string) $text;
        if (self::isAdmin()) {
            return $text;
        }
        return preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/u', '[hidden]', $text);
    }

    /**
     * Display name, Gmail-style: the sender's display name when they have one,
     * otherwise the first part of their email address (never the full address).
     */
    public static function customer(EmailThread $thread): string
    {
        $name = trim((string) $thread->customer_name);
        if ($name !== '' && !str_contains($name, '@')) {
            return $name;
        }
        $email = $name !== '' ? $name : (string) $thread->customer_email;
        $local = trim((string) strstr($email, '@', true));
        return $local !== '' ? $local : 'Client #' . $thread->id;
    }
}
