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
            'website'    => ['nullable', 'string', 'max:190'],
            'signature'  => ['nullable', 'string', 'max:2000'],
        ]);
        Mailbox::create($data);
        return back()->with('status', 'Brand added. Now connect its Gmail account.');
    }

    public function update(Request $request, Mailbox $mailbox)
    {
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'website'    => ['nullable', 'string', 'max:190'],
            'signature'  => ['nullable', 'string', 'max:2000'],
            'active'     => ['nullable', 'boolean'],
        ]);
        $data['active'] = $request->boolean('active');
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
        return redirect()->away(GmailClient::oauthUrl($mailbox->id));
    }

    /** One-click flow: sign in with Gmail and the brand is created automatically. */
    public function connectNew()
    {
        abort_unless(config('services.google.client_id'), 400, 'Google credentials missing in .env');
        return redirect()->away(GmailClient::oauthUrl(0)); // state 0 = create new mailbox
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
        ])->save();

        return $home('connected');
    }
}
