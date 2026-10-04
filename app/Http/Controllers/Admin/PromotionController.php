<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Promotions\SavePromotion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Media;
use App\Models\Promotion;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends Controller
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

        $records = Promotion::query()
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
            'title' => 'Khuyến mãi', 'resource' => 'promotions', 'records' => $records,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-catalog');

        return $this->form(new Promotion);
    }

    public function edit(Promotion $promotion): View
    {
        Gate::authorize('manage-catalog');

        return $this->form($promotion);
    }

    private function form(Promotion $record): View
    {
        return view('admin.promotions.form', [
            'record' => $record,
            'media' => Media::query()->latest()->limit(100)->get(),
            'vehicles' => Vehicle::query()->orderBy('name')->get(),
            'selectedVehicles' => $record->vehicles()->pluck('vehicles.id')->all(),
        ]);
    }

    public function store(PromotionRequest $request, SavePromotion $action): RedirectResponse
    {
        $record = $action->handle($request->validated(), new Promotion);

        return redirect()->route('admin.promotions.edit', $record)->with('success', 'Đã lưu nội dung.');
    }

    public function update(PromotionRequest $request, Promotion $promotion, SavePromotion $action): RedirectResponse
    {
        $action->handle($request->validated(), $promotion);

        return back()->with('success', 'Đã lưu nội dung.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        Gate::authorize('manage-catalog');
        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('success', 'Đã xóa nội dung.');
    }
}
