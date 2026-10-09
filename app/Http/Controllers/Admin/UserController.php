<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ListsRecords;

    public function index(Request $request)
    {
        $list = $this->listOptions($request, ['name', 'email', 'role', 'purchases_count', 'spent', 'created_at', 'is_active'], 'created_at', 'desc');
        $users = User::withCount(['orders as purchases_count' => fn ($q) => $q->where('status', 'paid')])
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))
            ->orderBy($list['sort'], $list['dir'])->orderByDesc('id')
            ->paginate($list['perPage'])
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $request->query('q')] + $list);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['nullable', 'in:customer,admin'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('messages.admin.own_account_change'));
        }
        $user->update(array_filter([
            'role' => $data['role'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn ($v) => $v !== null));

        return back()->with('success', __('messages.admin.customer_updated'));
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
        $message = __('messages.admin.bulk_done', ['count' => $users->count(), 'items' => __('messages.admin.nouns.accounts'), 'action' => __('messages.admin.actions.'.$data['action'])]);

        return back()->with($skipped ? 'warning' : 'success', $message.($skipped ? __('messages.admin.bulk_self_skipped') : ''));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('messages.admin.own_account_delete'));
        }
        $user->delete();

        return back()->with('success', __('messages.admin.customer_deleted'));
    }
}
