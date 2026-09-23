@extends('layouts.app')
@section('title', __('ui.nav.register'))

@section('content')
<x-auth-shell :title="__('ui.auth.register')" :subtitle="__('ui.auth.register_sub')">
    <form method="POST" action="{{ route('register') }}" class="space-y-[18px]">
        @csrf
        <input type="hidden" name="next" value="{{ $next }}">
        <h2 class="text-[clamp(1.3rem,2.8vw,1.6rem)]">{{ __('ui.auth.submit_register') }}</h2>
        <x-field :label="__('ui.auth.name')" name="name"><input name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Jane Doe" class="input"></x-field>
        <x-field :label="__('ui.auth.email')" name="email"><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="jane@example.com" class="input"></x-field>
        <x-field :label="__('ui.auth.password')" name="password" hint="At least 6 characters"><input type="password" name="password" required minlength="6" autocomplete="new-password" class="input"></x-field>
        <button class="btn-primary w-full py-[15px]">{{ __('ui.auth.submit_register') }} <x-icon name="arrow-right" size="15" /></button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">{{ __('ui.auth.have') }} <a href="{{ route('login', array_filter(['next' => $next])) }}" class="font-medium text-brand-bright hover:underline">{{ __('ui.nav.login') }}</a></p>
</x-auth-shell>
@endsection
