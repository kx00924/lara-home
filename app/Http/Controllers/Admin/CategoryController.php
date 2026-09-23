<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $items = Category::withCount('designs')->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('sort_order')->orderBy('name')->get();

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
