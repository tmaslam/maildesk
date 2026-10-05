@extends('layouts.app')

@section('title', 'Mailboxes — MailDesk')

@section('content')
    <div class="pad">
        @php
            $oauthMessages = [
                'connected' => ['flash', 'Gmail account connected successfully.'],
                'cancelled' => ['flash err', 'Google sign-in was cancelled.'],
                'failed'    => ['flash err', 'Google token exchange failed — try connecting again.'],
                'duplicate' => ['flash err', 'That Gmail account is already connected to another brand.'],
                'state'     => ['flash err', 'The connect link expired — click Connect Gmail again.'],
            ];
            $oauthMsg = $oauthMessages[request('msg')] ?? null;
        @endphp
        @if($oauthMsg)
            <div class="{{ $oauthMsg[0] }}" style="margin:0 0 16px">{{ $oauthMsg[1] }}</div>
        @endif

        @unless($googleReady)
            <div class="flash err" style="margin:0 0 16px">
                Google OAuth credentials are missing. Add <code>GOOGLE_CLIENT_ID</code> and
                <code>GOOGLE_CLIENT_SECRET</code> to the <code>.env</code> file (see README.md — it's free),
                then reload this page.
            </div>
        @endunless

        <div class="card connect-hero">
            <div class="connect-hero-inner">
                <svg width="44" height="44" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.2C12.4 13.7 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.4c-.5 2.9-2.2 5.4-4.6 7.1l7.5 5.8c4.4-4.1 6.8-10.1 6.8-17.4z"/><path fill="#FBBC05" d="M10.5 28.6a14.5 14.5 0 0 1 0-9.2l-7.9-6.2a24 24 0 0 0 0 21.6l7.9-6.2z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.6l-7.5-5.8c-2.1 1.4-4.7 2.2-7.7 2.2-6.3 0-11.6-4.2-13.5-9.9l-7.9 6.2C6.5 42.6 14.6 48 24 48z"/></svg>
                <div>
                    <h3 style="margin:0 0 4px">Add a brand with Gmail</h3>
                    <p style="margin:0; color:var(--text-2); font-size:13px">
                        One click — sign in with the brand's Gmail account and MailDesk picks up
                        the email address, brand name and website automatically.
                    </p>
                </div>
                @if($googleReady)
                    <a class="btn" style="white-space:nowrap" href="{{ route('mailboxes.connect-new') }}">Connect with Gmail</a>
                @else
                    <span class="pill warn">Add Google credentials to .env first</span>
                @endif
            </div>
        </div>

        <details class="card" style="padding:14px 18px">
            <summary style="cursor:pointer; color:var(--blue); font-weight:600">Or add a brand manually</summary>
            <form method="post" action="{{ route('mailboxes.store') }}" style="margin-top:14px">
                @csrf
                <div class="grid">
                    <div>
                        <label class="f">Brand name (shown to customers as the sender)</label>
                        <input class="f" name="brand_name" placeholder="1Dollar Digitizing" required>
                    </div>
                    <div>
                        <label class="f">Website (optional)</label>
                        <input class="f" name="website" placeholder="1dollardigitizing.com">
                    </div>
                </div>
                <div style="margin-top:12px">
                    <label class="f">Reply signature (added to every reply; keep it brand-only)</label>
                    <textarea class="f" name="signature" rows="3" placeholder="Best regards,&#10;1Dollar Digitizing Team&#10;www.1dollardigitizing.com"></textarea>
                </div>
                <div style="margin-top:12px"><button class="btn">Add brand</button></div>
            </form>
        </details>

        <div class="card">
            <h3>Connected mailboxes</h3>
            <table class="list">
                <tr><th>Brand</th><th>Gmail account</th><th>Status</th><th>Last sync</th><th></th></tr>
                @forelse($mailboxes as $mb)
                    <tr>
                        <td>
                            <span class="brand" style="background: {{ \App\Support\Palette::color($mb->id) }}; color:#fff; border-radius:999px; padding:3px 12px; font-size:11px; font-weight:600">
                                {{ $mb->brand_name }}
                            </span>
                            @if($mb->website)<div style="font-size:12px; color:var(--text-2); margin-top:4px">{{ $mb->website }}</div>@endif
                        </td>
                        <td>{{ $mb->email ?? '—' }}</td>
                        <td>
                            @if($mb->isConnected())
                                <span class="pill ok">Connected</span>
                            @else
                                <span class="pill warn">Not connected</span>
                            @endif
                            @unless($mb->active)<span class="pill warn">Paused</span>@endunless
                        </td>
                        <td style="font-size:12px; color:var(--text-2)">{{ $mb->last_synced_at?->diffForHumans() ?? '—' }}</td>
                        <td style="white-space:nowrap; text-align:right">
                            @if($mb->isConnected())
                                <form method="post" action="{{ route('mailboxes.pull', $mb) }}" style="display:inline"
                                      onsubmit="this.querySelector('button').textContent='Pulling…'; this.querySelector('button').disabled=true;">
                                    @csrf<button class="btn" type="submit">Pull recent emails</button>
                                </form>
                            @endif
                            @if($googleReady)
                                <a class="btn ghost" href="{{ route('mailboxes.connect', $mb) }}">
                                    {{ $mb->isConnected() ? 'Reconnect' : 'Connect Gmail' }}
                                </a>
                            @endif
                            <form method="post" action="{{ route('mailboxes.delete', $mb) }}" style="display:inline"
                                  data-confirm="Remove this brand and ALL its stored conversations? This cannot be undone."
                                  onsubmit="return confirm(this.dataset.confirm)">
                                @csrf<button class="btn danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="border-bottom:1px solid var(--border); padding-top:0">
                            <details>
                                <summary style="cursor:pointer; color:var(--blue); font-size:13px">Edit brand settings</summary>
                                <form method="post" action="{{ route('mailboxes.update', $mb) }}" style="margin-top:12px">
                                    @csrf
                                    <div class="grid">
                                        <div>
                                            <label class="f">Brand name</label>
                                            <input class="f" name="brand_name" value="{{ $mb->brand_name }}" required>
                                        </div>
                                        <div>
                                            <label class="f">Website</label>
                                            <input class="f" name="website" value="{{ $mb->website }}">
                                        </div>
                                    </div>
                                    <div style="margin-top:10px">
                                        <label class="f">Reply signature</label>
                                        <textarea class="f" name="signature" rows="3">{{ $mb->signature }}</textarea>
                                    </div>
                                    <div style="margin-top:10px; display:flex; align-items:center; gap:16px">
                                        <label style="display:flex; align-items:center; gap:6px; font-size:13px">
                                            <input type="checkbox" name="active" value="1" {{ $mb->active ? 'checked' : '' }}>
                                            Active (sync this mailbox)
                                        </label>
                                        <button class="btn">Save changes</button>
                                    </div>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--text-2)">No brands yet — add your first one above.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection

