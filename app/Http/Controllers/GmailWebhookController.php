<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Services\MailboxSyncer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives Gmail push notifications via Google Pub/Sub.
 * Worst case for a forged call is one extra sync, which is harmless,
 * but the shared-path token keeps casual noise out.
 */
class GmailWebhookController extends Controller
{
    const PATH_TOKEN = 'mdwh8k2p9x4v7q1z';

    public function handle(Request $request, string $token, MailboxSyncer $syncer)
    {
        if ($token !== self::PATH_TOKEN) {
            return response()->json(['ok' => false], 404);
        }

        // Pub/Sub push envelope: {"message":{"data": base64(json)}}
        $data = $request->input('message.data');
        $email = null;
        if ($data) {
            $decoded = json_decode(base64_decode(strtr($data, '-_', '+/')), true);
            $email = strtolower((string) ($decoded['emailAddress'] ?? ''));
        }

        $synced = 0;
        if ($email !== null && $email !== '') {
            $mb = Mailbox::where('email', $email)->where('active', true)->whereNotNull('refresh_token')->first();
            if ($mb) {
                try {
                    $synced = $syncer->sync($mb);
                } catch (\Throwable $e) {
                    Log::warning("Push sync failed for {$email}: " . $e->getMessage());
                }
            }
        }

        // Always 200 so Pub/Sub does not retry forever.
        return response()->json(['ok' => true, 'new' => $synced]);
    }
}
