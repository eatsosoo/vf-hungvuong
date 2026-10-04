<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveUser
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user): User {
            $admins = User::query()->where('role', 'admin')->where('is_active', true)->lockForUpdate()->get();
            $user = $user ? User::query()->lockForUpdate()->findOrFail($user->id) : new User;
            if ($user->exists && $admins->contains('id', $user->id) && $admins->count() === 1 &&
                ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages([
                    'role' => 'Phải giữ ít nhất một quản trị viên đang hoạt động.',
                ]);
            }
            $passwordChanged = ! empty($data['password']);
            if (! $passwordChanged) {
                unset($data['password']);
            }
            $user->fill($data)->save();
            if ($passwordChanged || ! $user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceFill(['remember_token' => null])->save();
            }

            return $user;
        });
    }
}
