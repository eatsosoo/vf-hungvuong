<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\SaveUser;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'role', 'is_active', 'id', 'updated_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = User::query();
        foreach (['name', 'email'] as $key) {
            if (filled($filters[$key] ?? null)) {
                $query->where($key, 'like', '%'.$filters[$key].'%');
            }
        }
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return view('admin.users.index', [
            'users' => $query->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->orderBy('id')
                ->paginate((int) ($filters['per_page'] ?? 20))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.form', ['account' => new User]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.form', ['account' => $user]);
    }

    public function store(UserRequest $request, SaveUser $action): RedirectResponse
    {
        $user = $action->handle($request->validated());

        return redirect()->route('admin.users.edit', $user)->with('success', 'Đã tạo tài khoản.');
    }

    public function update(UserRequest $request, User $user, SaveUser $action): RedirectResponse
    {
        $action->handle($request->validated(), $user);

        return back()->with('success', 'Đã cập nhật tài khoản.');
    }
}
