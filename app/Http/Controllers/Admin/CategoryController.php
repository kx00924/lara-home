<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $items = Category::withCount('designs')->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.taxonomy', [
            'kind' => 'categories',
            'items' => $items,
            'label' => ['one' => 'design style', 'many' => 'Design styles', 'hint' => 'Styles such as Japandi, Zen Minimalist or Modern Hanok. Every design belongs to one style.'],
            'q' => $request->query('q'),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return back()->with('success', 'Style created.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category->id));

        return back()->with('success', 'Style saved.');
    }

    public function destroy(Category $category)
    {
        if ($category->designs()->exists()) {
            return back()->with('error', 'This style still has designs. Move them first.');
        }
        $category->delete();

        return back()->with('success', 'Style deleted.');
    }

    /** Applies one action to many style(s) at once (activate, deactivate, delete). */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);
        $items = Category::whereIn('id', $data['ids'])->get();
        $skipped = 0;
        foreach ($items as $item) {
            match ($data['action']) {
                'activate' => $item->update(['is_active' => true]),
                'deactivate' => $item->update(['is_active' => false]),
                'delete' => $item->designs()->exists() ? $skipped++ : $item->delete(),
            };
        }
        $done = $items->count() - $skipped;
        $message = "{$done} style(s) ".['activate' => 'activated', 'deactivate' => 'deactivated', 'delete' => 'deleted'][$data['action']].'.';

        return back()->with($skipped ? 'warning' : 'success', $message.($skipped ? " {$skipped} still have designs and were kept." : ''));
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name,'.($id ?? 'NULL')],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
