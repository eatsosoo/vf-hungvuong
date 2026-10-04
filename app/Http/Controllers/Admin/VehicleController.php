<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Vehicles\SaveVehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleRequest;
use App\Models\Media;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
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

        $records = Vehicle::query()
            ->when(
                filled($data['q'] ?? null),
                fn (Builder $query): Builder => $query->where('name', 'like', '%'.$data['q'].'%'),
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
                    'name' => 'name',
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
            'title' => 'Danh mục xe', 'resource' => 'vehicles', 'records' => $records,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-catalog');

        return $this->form(new Vehicle);
    }

    public function edit(Vehicle $vehicle): View
    {
        Gate::authorize('manage-catalog');

        return $this->form($vehicle);
    }

    private function form(Vehicle $vehicle): View
    {
        $vehicle->load(['variants', 'colors']);
        $mediaIds = $vehicle->colors->pluck('media_id')->push($vehicle->media_id)->filter();

        return view('admin.vehicles.form', ['vehicle' => $vehicle,
            'media' => Media::query()->whereIn('id', $mediaIds)->get()]);
    }

    public function store(VehicleRequest $request, SaveVehicle $action): RedirectResponse
    {
        $vehicle = $action->handle($request->validated(), new Vehicle);

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('success', 'Đã lưu xe.');
    }

    public function update(VehicleRequest $request, Vehicle $vehicle, SaveVehicle $action): RedirectResponse
    {
        $action->handle($request->validated(), $vehicle);

        return back()->with('success', 'Đã lưu xe.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('manage-catalog');
        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('success', 'Đã xóa xe.');
    }
}
