<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Services\GmailClient;
use Illuminate\Http\Request;

class MailboxController extends Controller
{
    public function index()
    {
        $mailboxes = Mailbox::sidebar();
        $googleReady = (bool) config('services.google.client_id');
        return view('admin.mailboxes', compact('mailboxes', 'googleReady'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:190', 'unique:mailboxes,email'],
            'website'    => ['nullable', 'string', 'max:190'],
            'signature'  => ['nullable', 'string', 'max:2000'],
        ]);
        $data['email'] = isset($data['email']) ? strtolower($data['email']) : null;
        Mailbox::create($data);
        return back()->with('status', 'Brand added. Now connect its Gmail account.');
    }

    public function update(Request $request, Mailbox $mailbox)
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:190', 'unique:mailboxes,email,' . $mailbox->id],
            'website'    => ['nullable', 'string', 'max:190'],
            'signature'  => ['nullable', 'string', 'max:2000'],
            'active'     => ['nullable', 'boolean'],
        ]);
        $data['active'] = $request->boolean('active');
        if ($mailbox->isConnected()) {
            // The address of a connected mailbox comes from Gmail itself;
            // change it by reconnecting with another account.
            unset($data['email']);
        } else {
            $data['email'] = isset($data['email']) ? strtolower($data['email']) : null;
        }
        $mailbox->update($data);
        return back()->with('status', 'Mailbox updated.');
    }

    public function destroy(Mailbox $mailbox)
    {
        $mailbox->delete();
        return back()->with('status', 'Mailbox removed.');
    }

    public function connect(Mailbox $mailbox)
    {
        abort_unless(config('services.google.client_id'), 400, 'Google credentials missing in .env');
        return redirect()->away(GmailClient::oauthUrl($mailbox->id, $mailbox->email));
    }

    /** One-click flow: sign in with Gmail and the brand is created automatically. */
    public function connectNew()
    {
        abort_unless(config('services.google.client_id'), 400, 'Google credentials missing in .env');
        return redirect()->away(GmailClient::oauthUrl(0)); // state 0 = create new mailbox
    }

    /** Disconnect Gmail but keep the brand and its already-imported emails. */
    public function disconnect(Mailbox $mailbox)
    {
        $mailbox->forceFill([
            'refresh_token'    => null,
            'access_token'     => null,
            'token_expires_at' => null,
            'watch_expires_at' => null,
        ])->save();

        return back()->with('status', "{$mailbox->brand_name} disconnected from Gmail. Its emails are kept; reconnect any time.");
    }

    /**
     * "Pull recent emails" button: import the mailbox's older history on demand.
     * Works in ~45s batches (Cloudflare cuts requests at 100s); the page calls
     * this repeatedly via fetch until it reports done.
     */
    public function pull(Request $request, Mailbox $mailbox, \App\Services\MailboxSyncer $syncer)
    {
        $json = $request->expectsJson();
        if (!$mailbox->isConnected()) {
            return $json
                ? response()->json(['error' => 'Connect this mailbox to Gmail first.'], 422)
                : back()->withErrors(['google' => 'Connect this mailbox to Gmail first.']);
        }
        @set_time_limit(120);
        try {
            $n = $syncer->sync($mailbox, fullHistory: true, budgetSeconds: 45);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Pull failed: ' . $e->getMessage());
            return $json
                ? response()->json(['error' => 'Gmail was busy, try again in a minute.'], 503)
                : back()->withErrors(['google' => 'Pull failed — Gmail was busy, try again in a minute.']);
        }
        if ($json) {
            return response()->json(['imported' => $n, 'done' => !$syncer->timedOut]);
        }
        return back()->with('status', "Pulled {$n} older emails from {$mailbox->brand_name}."
            . ($syncer->timedOut ? ' Click again to continue.' : ''));
    }

    /** Derive a readable brand name from the Gmail address. */
    private static function brandNameFromEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $generic = ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'];
        $base = in_array(strtolower($domain), $generic, true)
            ? $local
            : preg_replace('/\.[a-z]+$/i', '', $domain);
        return ucwords(str_replace(['.', '_', '-'], ' ', $base)) ?: $email;
    }

    public function callback(Request $request)
    {
        // Google only allows http redirect URIs on localhost, so this callback can land
        // on a different host (and session) than APP_URL — report back via ?msg= code,
        // which the mailboxes page maps to a fixed text (never echoed raw).
        $home = fn (string $code) => redirect()->away(
            rtrim((string) config('app.url'), '/') . '/mailboxes?msg=' . $code
        );

        if ($request->get('error')) {
            return $home('cancelled');
        }

        $mailboxId = GmailClient::verifyState($request->get('state'));
        if ($mailboxId === null) {
            return $home('state');
        }

        try {
            $tokens = GmailClient::exchangeCode((string) $request->get('code'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OAuth exchange failed: ' . $e->getMessage());
            $tokens = [];
        }
        if (empty($tokens['access_token'])) {
            if ($tokens) {
                \Illuminate\Support\Facades\Log::error('OAuth exchange returned no access_token: ' . json_encode($tokens));
            }
            return $home('failed');
        }

        $profile = GmailClient::profile($tokens['access_token']);
        $email = strtolower($profile['emailAddress'] ?? '');

        if ($mailboxId === 0) {
            // One-click connect: reuse the brand if this Gmail is already linked,
            // otherwise create a new brand straight from the Gmail account.
            $mailbox = Mailbox::where('email', $email)->first();
            if (!$mailbox) {
                $name = self::brandNameFromEmail($email);
                [, $domain] = array_pad(explode('@', $email, 2), 2, '');
                $generic = in_array($domain, ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'yahoo.com'], true);
                $mailbox = Mailbox::create([
                    'brand_name' => $name,
                    'website'    => $generic ? null : $domain,
                    'signature'  => "Best regards,\n{$name} Team" . ($generic ? '' : "\nwww.{$domain}"),
                ]);
            }
        } else {
            $mailbox = Mailbox::findOrFail($mailboxId);
            if (Mailbox::where('email', $email)->where('id', '!=', $mailbox->id)->exists()) {
                return $home('duplicate');
            }
        }

        $mailbox->forceFill([
            'email'            => $email,
            'access_token'     => $tokens['access_token'],
            'refresh_token'    => $tokens['refresh_token'] ?? $mailbox->refresh_token,
            'token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600) - 30),
            // Forward-only by default: only mail from now on. The "Pull recent
            // emails" button fetches the older history on demand.
            'last_synced_at'   => now(),
        ])->save();

        return $home('connected');
    }
}
