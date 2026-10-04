<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxonomyRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    public function index(Request $request, string $kind): View
    {
        Gate::authorize('manage-content');
        $class = $kind === 'categories' ? Category::class : Tag::class;
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in(['name', 'slug', 'id', 'updated_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = $class::query();
        foreach (['name', 'slug'] as $key) {
            if (filled($filters[$key] ?? null)) {
                $query->where($key, 'like', '%'.$filters[$key].'%');
            }
        }

        return view('admin.taxonomies.index',
            ['kind' => $kind,
                'records' => $query->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->orderBy('id')
                    ->paginate((int) ($filters['per_page'] ?? 20))->withQueryString()]);
    }

    public function store(TaxonomyRequest $request, string $kind): RedirectResponse
    {
        $class = $kind === 'categories' ? Category::class : Tag::class;
        $class::query()->create($request->validated());

        return back()->with('success', 'Đã tạo phân loại.');
    }

    public function update(TaxonomyRequest $request, string $kind, int $taxonomy): RedirectResponse
    {
        $class = $kind === 'categories' ? Category::class : Tag::class;
        $class::query()->findOrFail($taxonomy)->update($request->validated());

        return back()->with('success', 'Đã cập nhật phân loại.');
    }

    public function destroy(string $kind, int $taxonomy): RedirectResponse
    {
        Gate::authorize('manage-content');
        $class = $kind === 'categories' ? Category::class : Tag::class;
        $class::query()->findOrFail($taxonomy)->delete();

        return back()->with('success', 'Đã xóa phân loại.');
    }
}
