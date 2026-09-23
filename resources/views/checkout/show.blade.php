@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
<div class="container-page max-w-4xl pt-28">
    <p class="eyebrow mb-2">{{ __('ui.checkout.title') }}</p>
    <h1 class="text-4xl">{{ __('ui.checkout.unlock') }} “{{ $order->design->title }}”</h1>
    <div class="mt-8 grid gap-6 md:grid-cols-[1fr_320px]">
        <div class="card overflow-hidden">
            <div class="aspect-[16/9]"><img src="{{ thumb($order->design->cover, 1200) }}" alt="" class="h-full w-full object-cover"></div>
            <div class="p-6">
                <p class="text-xs uppercase tracking-wider text-ink-faint">{{ $order->design->category->name }} · {{ $order->design->roomType->name }}</p>
                <h2 class="mt-1 text-2xl">{{ $order->design->title }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ $order->design->summary }}</p>
                <ul class="mt-4 grid gap-1 text-sm text-ink-muted sm:grid-cols-2">
                    <li>· {{ $order->design->images()->count() }} images, all angles</li><li>· Designer notes per image</li><li>· Full resolution</li><li>· Lifetime access</li>
                </ul>
            </div>
        </div>
        <aside class="space-y-4">
            <div class="card p-6">
                <div class="flex justify-between text-sm"><span class="text-ink-muted">Design</span><span>{{ price($order->amount) }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-ink-muted">Tax</span><span>{{ $site['currencySymbol'] }}0</span></div>
                <div class="mt-3 flex justify-between border-t border-line pt-3 font-medium"><span>Total</span><span class="font-serif text-2xl">{{ price($order->amount) }}</span></div>
                @if($demo)
                    <form method="POST" action="{{ route('checkout.pay', $order) }}" class="mt-5 space-y-2">
                        @csrf
                        <button name="outcome" value="success" class="btn-primary w-full"><x-icon name="lock" size="15" /> {{ __('ui.checkout.pay') }} {{ price($order->amount) }}</button>
                        <button name="outcome" value="fail" class="btn-ghost w-full text-xs">Simulate a declined card</button>
                    </form>
                @endif
                <p class="mt-4 flex items-start gap-2 text-xs text-ink-faint"><x-icon name="shield" size="14" class="mt-0.5" /> Secure one-time purchase. No subscription.</p>
            </div>
            @if($demo)
                <div class="flex items-start gap-2 rounded-xl2 bg-amber-500/10 p-4 text-xs text-amber-700 dark:text-amber-300">
                    <x-icon name="info" size="14" class="mt-0.5" />
                    <span>Demo mode: no card details are collected and no money moves. Set STRIPE_SECRET in .env to take real payments.</span>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
