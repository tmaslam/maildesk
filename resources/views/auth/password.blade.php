@extends('layouts.app')

@section('title', 'Change password — MailDesk')

@section('content')
    <div class="pad" style="max-width: 460px">
        <div class="card">
            <h3>Change password</h3>
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                <label class="f">Current password</label>
                <input class="f" type="password" name="current_password" autocomplete="current-password" required>
                <div style="margin-top:12px">
                    <label class="f">New password (min 8 characters)</label>
                    <input class="f" type="password" name="password" minlength="8" autocomplete="new-password" required>
                </div>
                <div style="margin-top:12px">
                    <label class="f">Confirm new password</label>
                    <input class="f" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                </div>
                <div style="margin-top:16px"><button class="btn">Update password</button></div>
            </form>
        </div>
    </div>
@endsection
