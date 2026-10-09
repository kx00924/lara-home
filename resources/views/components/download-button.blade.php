@props(['design'])
{{-- "Download all images" with progress: the link still works without JavaScript. --}}
<a href="{{ route('designs.download', $design) }}" download data-no-loader
   x-data="downloadButton(@js(route('designs.download', $design)), @js($design->slug.'-images.zip'), @js(['idle' => __('ui.design.download_all'), 'preparing' => __('ui.design.download_preparing'), 'progress' => __('ui.design.download_progress'), 'done' => __('ui.design.download_done'), 'failed' => __('ui.design.download_failed')]))"
   @click.prevent="start()" :aria-busy="busy" :class="busy ? 'pointer-events-none' : ''" {{ $attributes->merge(['class' => 'btn-ghost w-full']) }}>
    <span x-show="!busy"><x-icon name="download" size="15" /></span>
    <svg x-cloak x-show="busy" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    <span x-text="text">{{ __('ui.design.download_all') }}</span>
</a>
