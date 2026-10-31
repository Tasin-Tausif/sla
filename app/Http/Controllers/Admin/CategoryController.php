<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Admin\ConfigAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::withCount('tickets')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $category = Category::create($data);
        $audit->log('created', 'Category created', $category, [], $data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category, ConfigAuditLogger $audit): RedirectResponse
    {
        $old = $category->only(['name', 'slug', 'is_active']);
        $data = $this->validated($request, $category);
        $category->update($data);
        $audit->log('updated', 'Category updated', $category, $old, $data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category, ConfigAuditLogger $audit): RedirectResponse
    {
        if ($category->tickets()->exists()) {
            return back()->with('error', 'This category has tickets. Deactivate it instead.');
        }
        $old = $category->only(['name', 'slug', 'is_active']);
        $category->delete();
        $audit->log('deleted', 'Category deleted', $category, $old);

        return back()->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $id = $category?->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($id)],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($id)],
            'is_active' => ['boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        if (Category::query()->where('slug', $data['slug'])->when($category, fn ($query) => $query->where('id', '!=', $category->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'The slug has already been taken.']);
        }
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
