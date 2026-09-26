<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Proposal;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Gestion des catégories (thèmes de propositions, centres d'intérêt). */
class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::withCount([])->orderBy('position')->get()]);
    }

    private function rules(?Category $c = null): array
    {
        return [
            'name_ht' => ['required', 'string', 'max:100'],
            'name_fr' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug'.($c ? ','.$c->id : '')],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:9'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $data['slug'] ?: Str::slug($data['name_fr']);
        $data['is_active'] = $request->boolean('is_active', true);
        $c = Category::create($data);
        AuditLogger::log('category.create', $c);

        return back()->with('status', __('Catégorie créée.'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate($this->rules($category));
        $data['slug'] = $data['slug'] ?: $category->slug;
        $data['is_active'] = $request->boolean('is_active');
        $category->update($data);
        AuditLogger::log('category.update', $category);

        return back()->with('status', __('Catégorie mise à jour.'));
    }

    public function destroy(Category $category)
    {
        abort_if(Proposal::where('category_id', $category->id)->exists(), 422, __('Catégorie utilisée par des propositions : désactivez-la plutôt.'));
        $category->delete();
        AuditLogger::log('category.delete', null, ['slug' => $category->slug]);

        return back()->with('status', __('Catégorie supprimée.'));
    }
}
