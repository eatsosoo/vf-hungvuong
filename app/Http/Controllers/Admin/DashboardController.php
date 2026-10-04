<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $canLeads = $request->user()->can('viewAny', Lead::class);
        $leads = Lead::query()->accessibleTo($request->user());

        return view('admin.dashboard', [
            'newLeads' => $canLeads ? (clone $leads)->where('status', 'new')->count() : null,
            'quotes' => $canLeads ? (clone $leads)->where('type',
                'quote')->whereIn('status',
                    ['new',
                        'contacted'])->count() : null,
            'appointments' => $canLeads ? (clone $leads)->where('type',
                'test_drive')->whereIn('status',
                    ['new',
                        'confirmed'])->count() : null,
            'drafts' => $request->user()->can('viewAny', Post::class) ? Post::query()->where('status', 'draft')
                ->when($request->user()->role === UserRole::Editor,
                    fn ($query) => $query->where('user_id', $request->user()->id))->count() : null,
            'notifications' => $request->user()->unreadNotifications()->latest()->limit(10)->get(),
        ]);
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu thông báo đã đọc.');
    }
}
