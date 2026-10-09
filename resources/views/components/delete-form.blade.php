@props(['action', 'item', 'message' => 'This cannot be undone.', 'label' => 'Delete'])
{{-- Delete button that asks in the confirm dialog (window.confirmDelete) before the form is sent. --}}
<form method="POST" action="{{ $action }}" onsubmit="event.preventDefault(); confirmDelete(this, {{ Js::from($item) }}, {{ Js::from($message) }})" data-no-loader {{ $attributes }}>
    @csrf @method('DELETE')
    <button class="text-sm text-red-500 hover:underline">{{ $label }}</button>
</form>
