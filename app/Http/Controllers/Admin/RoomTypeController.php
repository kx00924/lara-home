<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $items = RoomType::withCount('designs')->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.taxonomy', [
            'kind' => 'room-types',
            'items' => $items,
            'label' => ['one' => 'room type', 'many' => 'Room types', 'hint' => 'Rooms such as Living Room or Kitchen. Every design belongs to one room type.'],
            'q' => $request->query('q'),
        ]);
    }

    public function store(Request $request)
    {
        RoomType::create($this->validated($request));

        return back()->with('success', 'Room type created.');
    }

    public function update(Request $request, RoomType $roomType)
    {
        $roomType->update($this->validated($request, $roomType->id));

        return back()->with('success', 'Room type saved.');
    }

    public function destroy(RoomType $roomType)
    {
        if ($roomType->designs()->exists()) {
            return back()->with('error', 'This room type still has designs. Move them first.');
        }
        $roomType->delete();

        return back()->with('success', 'Room type deleted.');
    }

    /** Applies one action to many room type(s) at once (activate, deactivate, delete). */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);
        $items = RoomType::whereIn('id', $data['ids'])->get();
        $skipped = 0;
        foreach ($items as $item) {
            match ($data['action']) {
                'activate' => $item->update(['is_active' => true]),
                'deactivate' => $item->update(['is_active' => false]),
                'delete' => $item->designs()->exists() ? $skipped++ : $item->delete(),
            };
        }
        $done = $items->count() - $skipped;
        $message = "{$done} room type(s) ".['activate' => 'activated', 'deactivate' => 'deactivated', 'delete' => 'deleted'][$data['action']].'.';

        return back()->with($skipped ? 'warning' : 'success', $message.($skipped ? " {$skipped} still have designs and were kept." : ''));
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:room_types,name,'.($id ?? 'NULL')],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['icon'] = $data['icon'] ?: 'home';

        return $data;
    }
}
