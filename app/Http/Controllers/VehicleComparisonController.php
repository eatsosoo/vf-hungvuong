<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleComparisonController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = Validator::make($request->query(), [
            'first' => ['bail', 'nullable', 'string', 'max:180',
                Rule::exists('vehicles', 'slug')->where('is_active', true)],
            'second' => ['bail', 'nullable', 'string', 'max:180', 'different:first',
                Rule::exists('vehicles', 'slug')->where('is_active', true)],
        ], [
            'first.exists' => __('Vui lòng chọn mẫu xe đang được hiển thị.'),
            'second.exists' => __('Vui lòng chọn mẫu xe đang được hiển thị.'),
            'first.string' => __('Mẫu xe phải là một lựa chọn hợp lệ.'),
            'second.string' => __('Mẫu xe phải là một lựa chọn hợp lệ.'),
            'first.max' => __('Mẫu xe không được vượt quá 180 ký tự.'),
            'second.max' => __('Mẫu xe không được vượt quá 180 ký tự.'),
            'second.different' => __('Vui lòng chọn hai mẫu xe khác nhau.'),
        ])->validate();
        $selectedSlugs = array_filter($data, fn (?string $slug): bool => $slug !== null && $slug !== '');
        $selectedVehicles = Vehicle::query()->active()->whereIn('slug', $selectedSlugs)
            ->with(['media', 'variants'])->get()->keyBy('slug');
        $firstVehicle = $selectedVehicles->get($data['first'] ?? '');
        $secondVehicle = $selectedVehicles->get($data['second'] ?? '');
        $routePrefix = app()->getLocale() === 'en' ? 'en.' : '';
        $breadcrumbs = [
            ['label' => __('Trang chủ'), 'url' => route($routePrefix.'home')],
            ['label' => __('Dòng xe'), 'url' => route($routePrefix.'vehicles.index')],
            ['label' => __('So sánh xe')],
        ];

        return view('site.vehicle-comparison', [
            'comparisonOptions' => Vehicle::query()->active()->orderBy('name')->orderBy('id')
                ->get(['id', 'name', 'slug']),
            'firstVehicle' => $firstVehicle,
            'secondVehicle' => $secondVehicle,
            'comparisonRows' => $this->comparisonRows($firstVehicle, $secondVehicle),
            'breadcrumbs' => $breadcrumbs,
            'seo' => [
                'title' => __('So sánh xe').' · '.(Setting::values()['site_name'] ?? 'VinFast Hùng Vương'),
                'description' => __('So sánh giá tham khảo, phiên bản và thông số của hai mẫu xe VinFast.'),
                'canonical' => route($routePrefix.'vehicles.compare', $selectedSlugs),
                'robots' => 'noindex,follow',
            ],
        ]);
    }

    /** @return list<array{label: string, first: ?string, second: ?string, different: bool}> */
    private function comparisonRows(?Vehicle $firstVehicle, ?Vehicle $secondVehicle): array
    {
        $firstSpecifications = $firstVehicle?->specifications ?? [];
        $secondSpecifications = $secondVehicle?->specifications ?? [];
        $labels = array_unique([...array_keys($firstSpecifications), ...array_keys($secondSpecifications)]);
        $rows = [];
        foreach ($labels as $label) {
            $firstValue = $this->specificationValue($firstSpecifications[$label] ?? null);
            $secondValue = $this->specificationValue($secondSpecifications[$label] ?? null);
            $rows[] = [
                'label' => (string) $label,
                'first' => $firstValue,
                'second' => $secondValue,
                'different' => $this->valueForComparison($firstValue) !== $this->valueForComparison($secondValue),
            ];
        }

        return $rows;
    }

    private function specificationValue(mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return is_string($value) ? $value
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function valueForComparison(?string $value): ?string
    {
        return $value === null ? null : preg_replace('/[ \t\n\r\f]+/', ' ', trim($value));
    }
}
