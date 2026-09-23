@extends('layouts.app')
@section('title', __('ui.dash.title'))

@section('content')
<div class="container-page pt-28">
    <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="eyebrow mb-2">{{ __('ui.dash.title') }}</p>
            <h1 class="text-4xl sm:text-5xl">Hello, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
            <p class="mt-2 text-ink-muted">{{ $purchases->count() }} {{ mb_strtolower(__('ui.dash.purchases')) }} · member since {{ auth()->user()->created_at->format('M j, Y') }}</p>
        </div>
        <div class="flex gap-1 overflow-x-auto rounded-pill bg-surface-2 p-1 scrollbar-none">
            @foreach([['purchases', 'images', __('ui.dash.purchases')], ['orders', 'receipt', 'Orders'], ['profile', 'user', __('ui.dash.profile')], ['appearance', 'palette', __('ui.dash.appearance')]] as [$k, $icon, $label])
                <a href="{{ route('dashboard', ['tab' => $k]) }}" class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2 text-sm font-medium transition {{ $tab === $k ? 'bg-surface shadow-soft' : 'text-ink-muted hover:text-ink' }}"><x-icon :name="$icon" size="15" /> {{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-10">
        @if($tab === 'purchases')
            @if($purchases->count())
                <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">@foreach($purchases as $d)<x-design-card :design="$d" owned />@endforeach</div>
            @else
                <x-empty-state icon="images" :title="__('ui.dash.empty')" text="Premium designs you unlock will appear here with every angle available.">
                    <a href="{{ route('designs.index', ['price' => 'paid']) }}" class="btn-primary">{{ __('ui.nav.designs') }}</a>
                </x-empty-state>
            @endif
        @elseif($tab === 'orders')
            <div class="card overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Design</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($orders as $o)
                            <tr>
                                <td class="font-medium">{{ $o->design?->title }}</td>
                                <td class="text-ink-muted">{{ $o->created_at->format('M j, Y') }}</td>
                                <td>{{ price($o->amount) }}</td>
                                <td><span class="badge {{ ['paid' => 'badge-free', 'pending' => 'badge-warn', 'failed' => 'badge-danger', 'refunded' => 'badge-neutral'][$o->status] }}">{{ $o->status }}</span></td>
                                <td class="text-right">@if($o->status === 'pending')<a href="{{ route('checkout.show', $o) }}" class="text-sm text-accent hover:underline">Complete</a>@elseif($o->design)<a href="{{ route('designs.show', $o->design) }}" class="text-sm text-accent hover:underline">Open</a>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-ink-muted">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'profile')
            <form method="POST" action="{{ route('dashboard.profile') }}" class="card max-w-lg space-y-4 p-6">
                @csrf @method('PUT')
                <x-field :label="__('ui.auth.name')" name="name"><input name="name" value="{{ old('name', auth()->user()->name) }}" class="input" required></x-field>
                <x-field :label="__('ui.auth.email')"><input value="{{ auth()->user()->email }}" class="input" disabled></x-field>
                <x-field label="New password" name="password" hint="Leave blank to keep the current password"><input type="password" name="password" class="input" minlength="6"></x-field>
                <button class="btn-primary">{{ __('ui.common.save') }}</button>
            </form>
        @else
            <div class="card max-w-lg p-6"><h2 class="mb-6 text-2xl">{{ __('ui.theme.title') }}</h2><x-theme-panel /></div>
        @endif
    </div>
</div>
@endsection
