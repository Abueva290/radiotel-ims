<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw("FIELD(role, 'admin', 'secretary', 'technical_head', 'staff')")
            ->orderBy('name')
            ->get();

        $counts = User::where('status', 'active')
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('users.index', compact('users', 'counts'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'role'     => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create($data + [
            'status'               => 'active',
            'must_change_password' => true,
        ]);

        return redirect()->route('users.index')->with('success',
            "Account created for {$user->name}. Give them the temporary password — they must change it on first login.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role'  => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', "{$user->name} updated.");
    }

    // Admin sets a new temporary password (e.g. employee forgot theirs)
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', Password::min(8)],
        ]);

        $user->update([
            'password'             => $data['password'],
            'must_change_password' => true,
        ]);

        return redirect()->route('users.edit', $user)->with('success',
            "Temporary password set for {$user->name}. They must change it on next login.");
    }

    // Disable instead of delete: keeps who-did-what records intact
    public function toggleStatus(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot disable your own account.']);
        }

        $user->update(['status' => $user->isActive() ? 'disabled' : 'active']);

        return back()->with('success', $user->isActive()
            ? "{$user->name} can log in again."
            : "{$user->name} has been disabled and can no longer log in.");
    }
}