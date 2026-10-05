<?php

namespace App\Support;

/**
 * Splits an email body into the new text and the quoted history
 * ("On ... wrote:" blocks / "> " lines), Gmail-style.
 */
class Quote
{
    /** @return array{0: string, 1: string} [main, quoted] */
    public static function split(?string $body): array
    {
        $body = (string) $body;
        $lines = preg_split('/\r?\n/', $body);
        $cut = null;

        foreach ($lines as $i => $line) {
            $t = trim($line);
            // "On Mon, 5 Oct 2026 at 18:37, Name <a@b.com> wrote:"
            if (preg_match('/^On .{5,160} wrote:\s*$/u', $t)) {
                $cut = $i;
                break;
            }
            // First block of "> quoted" lines
            if ($cut === null && str_starts_with($t, '>') && $i > 0) {
                $cut = $i;
                break;
            }
            // Outlook style: "-----Original Message-----"
            if (preg_match('/^-{2,}\s*Original Message\s*-{2,}$/i', $t)) {
                $cut = $i;
                break;
            }
        }

        if ($cut === null || $cut === 0) {
            return [$body, ''];
        }

        $main = rtrim(implode("\n", array_slice($lines, 0, $cut)));
        $quoted = trim(implode("\n", array_slice($lines, $cut)));

        return $main === '' ? [$body, ''] : [$main, $quoted];
    }
}
