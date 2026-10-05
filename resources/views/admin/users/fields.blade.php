@php($fieldPrefix = $fieldPrefix ?? 'account')
<x-field name="name" :id="$fieldPrefix.'-name'" label="Họ tên" :value="$account->name" required maxlength="100" />
<x-field name="email" :id="$fieldPrefix.'-email'" label="Email" type="email"
    :value="$account->email" required maxlength="254" />
<x-select name="role" :id="$fieldPrefix.'-role'" label="Vai trò" required>
    @foreach(App\Enums\UserRole::cases() as $role)
        <option value="{{ $role->value }}"
            @selected(old('role', $account->role?->value ?? 'sales') === $role->value)>
            {{ __('studio.role.'.$role->value) }}
        </option>
    @endforeach
</x-select>
<x-select name="is_active" :id="$fieldPrefix.'-active'" label="Hoạt động" required>
    <option value="1" @selected((string) old('is_active', $account->is_active ?? true) === '1')>Bật</option>
    <option value="0" @selected((string) old('is_active', $account->is_active ?? true) === '0')>Khóa</option>
</x-select>
<p class="mb-5 text-sm text-muted">
    Mật khẩu ít nhất 12 ký tự gồm chữ hoa/thường, số và ký hiệu.
    <span data-drawer-password-hint @if(! $account->exists) hidden @endif>
        Khi sửa, để trống để giữ mật khẩu.
    </span>
</p>
<x-field name="password" :id="$fieldPrefix.'-password'" label="Mật khẩu" type="password"
    :required="! $account->exists" maxlength="255" minlength="12" />
<x-field name="password_confirmation" :id="$fieldPrefix.'-password-confirmation'" label="Nhập lại mật khẩu"
    type="password" :required="! $account->exists" maxlength="255" minlength="12" />
