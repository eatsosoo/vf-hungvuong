<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Media;
use App\Models\Page;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-catalog');
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in(['name', 'slug', 'is_active', 'id', 'updated_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $records = Page::query()
            ->when(
                filled($data['q'] ?? null),
                fn (Builder $query): Builder => $query->where('title', 'like', '%'.$data['q'].'%'),
            )
            ->when(
                filled($data['slug'] ?? null),
                fn (Builder $query): Builder => $query->where('slug', 'like', '%'.$data['slug'].'%'),
            )
            ->when(
                isset($data['is_active']),
                fn (Builder $query): Builder => $query->where('is_active', $data['is_active']),
            )
            ->orderBy(
                match ($data['sort'] ?? null) {
                    'name' => 'title',
                    'slug' => 'slug',
                    'is_active' => 'is_active',
                    'id' => 'id',
                    'updated_at' => 'updated_at',
                    default => 'created_at',
                },
                $data['direction'] ?? (isset($data['sort']) ? 'asc' : 'desc'),
            )
            ->orderByDesc('id')
            ->paginate((int) ($data['per_page'] ?? 20))
            ->withQueryString();

        return view('admin.records.index', [
            'title' => 'Trang nội dung', 'resource' => 'pages', 'records' => $records,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-catalog');

        return $this->form(new Page);
    }

    public function edit(Page $page): View
    {
        Gate::authorize('manage-catalog');

        return $this->form($page);
    }

    private function form(Page $record): View
    {
        return view('admin.records.form', ['record' => $record, 'resource' => 'pages', 'title' => 'Trang nội dung',
            'fields' => ['title',
                'slug',
                'body',
                'seo_title',
                'seo_description',
                'is_active'],
            'media' => Media::query()->latest()->limit(100)->get(),
            'vehicles' => Vehicle::query()->orderBy('name')->get(),
            'selectedVehicles' => []]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $record = $this->save($request->validated(), new Page);

        return redirect()->route('admin.pages.edit', $record)->with('success', 'Đã lưu nội dung.');
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $this->save($request->validated(), $page);

        return back()->with('success', 'Đã lưu nội dung.');
    }

    /** @param array<string, mixed> $data */
    private function save(array $data, Page $record): Page
    {
        return DB::transaction(function () use ($data, $record): Page {
            $record->fill($data)->save();

            return $record;
        });
    }

    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('manage-catalog');
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Đã xóa nội dung.');
    }
}
