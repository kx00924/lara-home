<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $items = RoomType::withCount('designs')->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('sort_order')->orderBy('name')->get();

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
