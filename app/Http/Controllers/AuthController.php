<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('inbox');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
            'portal'   => ['required', 'in:admin,agent'],
        ]);

        $ok = Auth::attempt([
            'username' => strtolower(trim($data['username'])),
            'password' => $data['password'],
        ], true);

        if (!$ok) {
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username', 'portal');
        }

        // The selected portal must match the account's actual role.
        if (($data['portal'] === 'admin') !== Auth::user()->isAdmin()) {
            Auth::logout();
            $msg = $data['portal'] === 'admin'
                ? 'This account is not a Super Admin — use the Team option.'
                : 'This is a Super Admin account — use the Super Admin option.';
            return back()->withErrors(['username' => $msg])->onlyInput('username', 'portal');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('inbox'));
    }

    public function showPassword()
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'Current password is incorrect.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('inbox')->with('status', 'Password changed successfully.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
