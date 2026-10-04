<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Media;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-settings');

        return view('admin.settings.form',
            ['settings' => Setting::values(),
                'media' => Media::query()->latest()->limit(100)->get()]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated() as $key => $value) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return back()->with('success', 'Đã lưu cấu hình.');
    }
}
