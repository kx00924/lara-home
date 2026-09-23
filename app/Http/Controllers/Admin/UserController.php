<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount(['orders as purchases_count' => fn ($q) => $q->where('status', 'paid')])
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $request->query('q')]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:customer,admin'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role or status.');
        }
        $user->update(array_filter([
            'role' => $data['role'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn ($v) => $v !== null));

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();

        return back()->with('success', 'Customer deleted.');
    }
}
