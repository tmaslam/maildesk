@extends('layouts.app')

@section('title', 'Team — MailDesk')

@section('content')
    <div class="pad">
        <div class="card">
            <h3>Add a team member</h3>
            <form method="post" action="{{ route('users.store') }}">
                @csrf
                <div class="grid">
                    <div>
                        <label class="f">Name</label>
                        <input class="f" name="name" required>
                    </div>
                    <div>
                        <label class="f">Username (for login)</label>
                        <input class="f" name="username" pattern="[a-zA-Z0-9_.\-]+" minlength="3" required>
                    </div>
                    <div>
                        <label class="f">Password</label>
                        <input class="f" type="text" name="password" minlength="6" required>
                    </div>
                    <div>
                        <label class="f">Role</label>
                        <select class="f" name="role">
                            <option value="agent">Team — customer emails hidden</option>
                            <option value="admin">Super Admin — full access</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px"><button class="btn">Add member</button></div>
            </form>
        </div>

        <div class="card">
            <h3>Team</h3>
            <table class="list">
                <tr><th>Name</th><th>Username</th><th>Role</th><th></th></tr>
                @foreach($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td><span class="pill {{ $u->role }}">{{ $u->role === 'admin' ? 'Super Admin' : 'Team' }}</span></td>
                        <td style="text-align:right; white-space:nowrap">
                            <form method="post" action="{{ route('users.password', $u) }}" style="display:inline-flex; gap:6px; align-items:center">
                                @csrf
                                <input class="f" style="width:150px; padding:7px 10px" type="text" name="password"
                                       placeholder="New password" minlength="6" required>
                                <button class="btn ghost">Set</button>
                            </form>
                            @if($u->id !== auth()->id())
                                <form method="post" action="{{ route('users.delete', $u) }}" style="display:inline"
                                      data-confirm="Remove this team member?"
                                      onsubmit="return confirm(this.dataset.confirm)">
                                    @csrf<button class="btn danger">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
