@extends('layouts.app')
@section('title', 'Unlocked')

@section('content')
<div class="container-page flex min-h-[70vh] items-center justify-center pt-24">
    @if($order->status === 'paid')
        <div class="card max-w-lg p-10 text-center animate-rise">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500"><x-icon name="check-circle" size="32" /></div>
            <p class="eyebrow mb-2">{{ __('ui.design.owned') }}</p>
            <h1 class="text-3xl">{{ __('ui.checkout.success_title') }}</h1>
            <p class="mt-3 text-ink-muted">“{{ $order->design->title }}” {{ __('ui.checkout.success_text') }}</p>
            <div class="mt-8 flex flex-col gap-2 sm:flex-row sm:justify-center">
                <a href="{{ route('designs.show', $order->design) }}#gallery" class="btn-primary">{{ __('ui.design.gallery') }} <x-icon name="arrow-right" size="15" /></a>
                <a href="{{ route('dashboard') }}" class="btn-ghost">{{ __('ui.nav.dashboard') }}</a>
            </div>
        </div>
    @else
        <x-empty-state icon="x-circle" title="Payment not completed" text="If you were charged, contact us and we will sort it out.">
            <a href="{{ route('checkout.show', $order) }}" class="btn-primary">Try again</a>
        </x-empty-state>
    @endif
</div>
@endsection
