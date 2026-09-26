<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.change-password', [
            'forced' => $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.different'                => 'The new password must be different from the current one.',
        ]);

        $request->user()->update([
            'password'             => $request->password,
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }
}