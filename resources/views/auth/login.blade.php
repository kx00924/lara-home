@extends('layouts.app')
@section('title', __('ui.nav.login'))

@section('content')
<x-auth-shell :title="__('ui.auth.login')" :subtitle="__('ui.auth.login_sub')">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="next" value="{{ $next }}">
        <x-field :label="__('ui.auth.email')" name="email"><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="input"></x-field>
        <x-field :label="__('ui.auth.password')" name="password"><input type="password" name="password" required autocomplete="current-password" class="input"></x-field>
        <label class="flex items-center gap-2 text-sm text-ink-muted"><input type="checkbox" name="remember" value="1" class="accent-accent"> Remember me</label>
        <button class="btn-primary w-full">{{ __('ui.auth.submit_login') }}</button>
    </form>
    <p class="mt-6 text-center text-sm text-ink-muted">{{ __('ui.auth.none') }} <a href="{{ route('register', array_filter(['next' => $next])) }}" class="font-medium text-accent hover:underline">{{ __('ui.nav.register') }}</a></p>
    <div class="mt-6 rounded-xl2 bg-surface-2 p-4 text-xs text-ink-muted">
        <p class="mb-1 font-semibold uppercase tracking-wider">Demo accounts</p>
        <p>Admin: admin@home.studio / Admin123!</p>
        <p>Customer: demo@example.com / Demo123!</p>
    </div>
</x-auth-shell>
@endsection
