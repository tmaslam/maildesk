@extends('layouts.app')

@section('title', 'Activity — MailDesk')

@section('content')
    <div class="pad">
        <div class="card">
            <h3>Recent activity</h3>
            <p style="color:var(--text-2); font-size:13px; margin-top:-6px">
                Kis team member ne kaunsi email parhi aur kis ne reply bheja — naya sab se upar.
            </p>
            <table class="list">
                <tr><th></th><th>Member</th><th>Action</th><th>Conversation</th><th>Brand</th><th>When</th></tr>
                @forelse($events as $e)
                    <tr>
                        <td style="width:34px">
                            <span class="avatar" style="width:28px;height:28px;font-size:12px;background:{{ $e['type'] === 'reply' ? '#188038' : 'var(--blue)' }}">
                                {{ mb_substr($e['user']->name, 0, 1) }}
                            </span>
                        </td>
                        <td>
                            {{ $e['user']->name }}
                            <span class="pill {{ $e['user']->role }}">{{ $e['user']->isAdmin() ? 'Super Admin' : 'Team' }}</span>
                        </td>
                        <td>{{ $e['type'] === 'reply' ? '✉ Replied' : '👁 Read' }}</td>
                        <td>
                            <a href="{{ route('thread', $e['thread']) }}" style="color:var(--blue)">
                                {{ \Illuminate\Support\Str::limit(\App\Support\Mask::body($e['thread']->subject), 50) ?: '(no subject)' }}
                            </a>
                            <small style="color:var(--text-2)"> — {{ \App\Support\Mask::customer($e['thread']) }}</small>
                        </td>
                        <td>
                            <span class="brand" style="background: {{ \App\Support\Palette::color($e['thread']->mailbox_id) }}; color:#fff; border-radius:999px; padding:2px 10px; font-size:11px; font-weight:600">
                                {{ $e['thread']->mailbox->brand_name }}
                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--text-2); white-space:nowrap">{{ $e['time']->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="color:var(--text-2)">Abhi koi activity record nahi.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
