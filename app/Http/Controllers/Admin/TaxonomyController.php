<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxonomyRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'drawer' => ['nullable', 'regex:/\A(?:new|[1-9][0-9]*)\z/'],
        ]);
        $query = $class::query()->withCount('posts');
        foreach (['name', 'slug'] as $key) {
            if (filled($filters[$key] ?? null)) {
                $query->where($key, 'like', '%'.$filters[$key].'%');
            }
        }

        $selected = $request->old('_drawer') === 'taxonomy'
            ? $request->old('_record') : $request->query('drawer');
        $record = is_scalar($selected) && ctype_digit((string) $selected)
            ? $class::query()->findOrFail($selected) : new $class;
        $request->query->remove('drawer');

        return view('admin.taxonomies.index',
            ['kind' => $kind,
                'taxonomy' => $record,
                'openDrawer' => $selected !== null || $request->old('_drawer') === 'taxonomy',
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
        DB::transaction(function () use ($class, $kind, $taxonomy): void {
            $record = $class::query()->lockForUpdate()->findOrFail($taxonomy);
            if ($kind === 'categories' && $record->posts()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Không thể xóa danh mục đang có bài viết. '
                        .'Hãy chuyển bài viết sang danh mục khác trước.',
                ]);
            }
            $record->delete();
        });

        return back()->with('success', 'Đã xóa phân loại.');
    }
}
