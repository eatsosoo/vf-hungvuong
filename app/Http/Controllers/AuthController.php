<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt([...$request->validated(), 'is_active' => true])) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }
        $request->session()->regenerate();
        AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => 'login',
            'subject_type' => User::class, 'subject_id' => $request->user()->id, 'changed_fields' => []]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editPassword(): View
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $request->user()->update(['password' => $data['password']]);
        DB::table('sessions')->where('user_id', $request->user()->id)->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã đổi mật khẩu. Vui lòng đăng nhập lại.');
    }
}
