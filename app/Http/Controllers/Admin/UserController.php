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

    /** Applies one action to many accounts at once; the signed-in admin is always skipped. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate,make_customer,make_admin,delete'],
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);
        $users = User::whereIn('id', $data['ids'])->where('id', '!=', $request->user()->id)->get();
        foreach ($users as $user) {
            match ($data['action']) {
                'activate' => $user->update(['is_active' => true]),
                'deactivate' => $user->update(['is_active' => false]),
                'make_customer' => $user->update(['role' => 'customer']),
                'make_admin' => $user->update(['role' => 'admin']),
                'delete' => $user->delete(),
            };
        }
        $skipped = count($data['ids']) - $users->count();
        $message = "{$users->count()} account(s) ".['activate' => 'activated', 'deactivate' => 'deactivated', 'make_customer' => 'set to customer', 'make_admin' => 'set to admin', 'delete' => 'deleted'][$data['action']].'.';

        return back()->with($skipped ? 'warning' : 'success', $message.($skipped ? ' Your own account was skipped.' : ''));
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
