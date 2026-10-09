{{-- Top progress bar for the first paint, then a full overlay while the next page loads (Alpine $store.loader). --}}
<div id="page-progress" class="pointer-events-none fixed inset-x-0 top-0 z-[130] h-[3px] origin-left" style="background:{{ $site['accentColor'] ?? '#3b82f6' }};transform:scaleX(.25);transition:transform .5s ease,opacity .3s ease"></div>
<script>
    (function () {
        var bar = document.getElementById('page-progress');
        if (!bar) return;
        window.addEventListener('load', function () {
            bar.style.transform = 'scaleX(1)';
            setTimeout(function () { bar.style.opacity = '0'; setTimeout(function () { bar.remove(); }, 300); }, 200);
        });
    })();
</script>
<div x-data x-cloak x-show="$store.loader.visible" x-transition.opacity.duration.200ms
     class="fixed inset-0 z-[125] flex flex-col items-center justify-center gap-4 bg-black/55 text-white backdrop-blur-sm" aria-live="polite" aria-busy="true">
    <svg class="h-10 w-10 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    <p class="text-xs font-semibold uppercase tracking-[0.25em] opacity-80">{{ __('ui.common.loading') }}</p>
</div>
