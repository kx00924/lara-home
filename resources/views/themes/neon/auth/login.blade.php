@extends('layouts.app')
@section('title', __('ui.nav.login'))

@section('content')
<x-auth-shell :title="__('ui.auth.login')" :subtitle="__('ui.auth.login_sub')">
    <form method="POST" action="{{ route('login') }}" class="space-y-[18px]">
        @csrf
        <input type="hidden" name="next" value="{{ $next }}">
        <h2 class="text-[clamp(1.3rem,2.8vw,1.6rem)]">{{ __('ui.auth.submit_login') }}</h2>
        <x-field :label="__('ui.auth.email')" name="email"><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com" class="input"></x-field>
        <x-field :label="__('ui.auth.password')" name="password"><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="input"></x-field>
        <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="remember" value="1" class="accent-brand"> Remember me</label>
        <button class="btn-primary w-full py-[15px]">{{ __('ui.auth.submit_login') }} <x-icon name="arrow-right" size="15" /></button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">{{ __('ui.auth.none') }} <a href="{{ route('register', array_filter(['next' => $next])) }}" class="font-medium text-brand-bright hover:underline">{{ __('ui.nav.register') }}</a></p>
    <p class="mt-6 rounded-lg border border-dashed border-brand/60 bg-brand/[0.06] px-3.5 py-3 font-mono text-[0.74rem] leading-relaxed text-brand-bright">admin@home.studio / Admin123!<br>demo@example.com / Demo123!</p>
</x-auth-shell>
@endsection
