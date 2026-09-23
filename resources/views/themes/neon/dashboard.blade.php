@extends('layouts.app')
@section('title', __('ui.dash.title'))

@section('content')
    <x-page-hero :eyebrow="__('ui.dash.title')" :title="'Hello, '.explode(' ', auth()->user()->name)[0].'.'" :subtitle="$purchases->count().' '.mb_strtolower(__('ui.dash.purchases')).' · member since '.auth()->user()->created_at->format('M j, Y')">
        <div class="flex flex-wrap gap-2.5" role="tablist">
            @foreach([['purchases', 'images', __('ui.dash.purchases')], ['orders', 'receipt', 'Orders'], ['profile', 'user', __('ui.dash.profile')]] as [$k, $icon, $label])
                <a href="{{ route('dashboard', ['tab' => $k]) }}" class="pill {{ $tab === $k ? 'pill-active' : '' }}" role="tab" aria-selected="{{ $tab === $k ? 'true' : 'false' }}"><x-icon :name="$icon" size="14" /> {{ $label }}</a>
            @endforeach
        </div>
    </x-page-hero>

    <section class="shell grid gap-10 pb-24 pt-[clamp(56px,8vw,90px)] lg:grid-cols-[0.7fr_1.3fr]">
        <aside>
            <div class="card-static sticky top-24 px-[26px] py-7">
                <div class="flex items-center gap-4">
                    <span class="grid size-14 place-items-center rounded-xl bg-linear-to-br from-brand-bright to-brand font-mono text-xl font-bold text-canvas shadow-glow">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><strong class="block">{{ auth()->user()->name }}</strong><span class="text-[0.85rem] text-muted">{{ auth()->user()->email }}</span></div>
                </div>
                <h3 class="mb-4 mt-7 text-[1.02rem] text-brand-bright">Quick facts</h3>
                <dl class="flex flex-col">
                    @foreach([[__('ui.dash.purchases'), $purchases->count()], ['Orders', $orders->count()], ['Role', ucfirst(auth()->user()->role)], ['Member since', auth()->user()->created_at->format('M Y')]] as [$k, $v])
                        <div class="border-t border-brand/[0.13] py-3.5 first:border-t-0 first:pt-0"><dt class="text-[0.72rem] uppercase tracking-[0.12em] text-faint">{{ $k }}</dt><dd class="mt-1 text-[0.93rem] font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                <a href="{{ route('designs.index', ['price' => 'paid']) }}" class="btn-ghost mt-5 w-full">{{ __('ui.nav.designs') }} <x-icon name="arrow-right" size="15" /></a>
            </div>
        </aside>

        <div>
            @if($tab === 'purchases')
                @if($purchases->count())
                    <div class="grid gap-[22px] sm:grid-cols-2">@foreach($purchases as $d)<x-design-card :design="$d" owned />@endforeach</div>
                @else
                    <div class="card-static px-8 py-16 text-center"><span class="icon-tile mx-auto mb-4 size-14"><x-icon name="images" size="24" /></span><h3 class="text-[1.3rem]">{{ __('ui.dash.empty') }}</h3><a href="{{ route('designs.index', ['price' => 'paid']) }}" class="btn-primary mt-6">{{ __('ui.nav.designs') }}</a></div>
                @endif
            @elseif($tab === 'orders')
                <div class="card-static overflow-x-auto">
                    <table class="table-base">
                        <thead><tr><th>Design</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($orders as $o)
                                <tr><td class="font-medium">{{ $o->design?->title }}</td><td class="font-mono text-[0.78rem] text-muted">{{ $o->created_at->format('Y-m-d') }}</td><td class="font-mono">{{ price($o->amount) }}</td><td><span class="badge {{ ['paid' => 'badge-free', 'pending' => 'badge-warn', 'failed' => 'badge-danger', 'refunded' => 'badge-neutral'][$o->status] }}">{{ $o->status }}</span></td><td class="text-right">@if($o->status === 'pending')<a href="{{ route('checkout.show', $o) }}" class="text-sm text-brand-bright hover:underline">Complete</a>@elseif($o->design)<a href="{{ route('designs.show', $o->design) }}" class="text-sm text-brand-bright hover:underline">Open</a>@endif</td></tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-muted">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <form method="POST" action="{{ route('dashboard.profile') }}" class="card-static space-y-[18px] px-[22px] py-7 sm:px-[34px] sm:py-9">
                    @csrf @method('PUT')
                    <h2 class="text-[clamp(1.3rem,2.8vw,1.6rem)]">{{ __('ui.dash.profile') }}</h2>
                    <x-field :label="__('ui.auth.name')" name="name"><input name="name" value="{{ old('name', auth()->user()->name) }}" class="input" required></x-field>
                    <x-field :label="__('ui.auth.email')"><input value="{{ auth()->user()->email }}" class="input opacity-60" disabled></x-field>
                    <x-field label="New password" name="password" hint="Leave blank to keep the current password"><input type="password" name="password" class="input" minlength="6"></x-field>
                    <button class="btn-primary w-full">{{ __('ui.common.save') }}</button>
                </form>
            @endif
        </div>
    </section>
@endsection
