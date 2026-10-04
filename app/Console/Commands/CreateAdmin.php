<?php

namespace App\Console\Commands;

use App\Actions\Users\SaveUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name=Quản trị viên}';

    protected $description = 'Tạo admin với mật khẩu nhập ẩn; không có mật khẩu mặc định';

    public function handle(SaveUser $action): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Chạy tương tác để nhập mật khẩu riêng. Không truyền mật khẩu vào command line.');

            return self::FAILURE;
        }
        $password = $this->secret('Mật khẩu (ít nhất 12 ký tự, chữ hoa/thường, số và ký hiệu)');
        $confirmation = $this->secret('Nhập lại mật khẩu');
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name'),
            'password' => $password, 'password_confirmation' => $confirmation, 'role' => 'admin', 'is_active' => true];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'max:254', 'unique:users'],
            'name' => ['required', 'string', 'max:100'], 'password' => ['required', 'confirmed', 'max:255',
                Password::min(12)->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $action->handle($data);
        $this->info('Đã tạo quản trị viên.');

        return self::SUCCESS;
    }
}
