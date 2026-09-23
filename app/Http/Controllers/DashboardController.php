<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['purchases', 'orders', 'profile', 'appearance']) ? $request->query('tab') : 'purchases';
        $orders = $request->user()->orders()->with(['design.category', 'design.roomType'])->latest()->get();

        return view('dashboard', [
            'tab' => $tab,
            'orders' => $orders,
            'purchases' => $orders->where('status', 'paid')->pluck('design')->filter()->unique('id'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', Password::min(6)],
        ]);
        $user = $request->user();
        $user->name = $data['name'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()->route('dashboard', ['tab' => 'profile'])->with('success', __('ui.dash.saved'));
    }
}
