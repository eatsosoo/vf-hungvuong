<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_creation_requires_an_interactive_password(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.test', '--no-interaction' => true])
            ->expectsOutput(
                'Chạy tương tác để nhập mật khẩu riêng. Không truyền mật khẩu vào command line.'
            )
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_interactive_admin_creation_hashes_password_and_creates_active_admin(): void
    {
        $password = 'PrivatePassword!123';
        $this->artisan('admin:create', ['email' => 'admin@example.test', '--name' => 'Quản trị mới'])
            ->expectsQuestion(
                'Mật khẩu (ít nhất 12 ký tự, chữ hoa/thường, số và ký hiệu)', $password
            )
            ->expectsQuestion('Nhập lại mật khẩu', $password)
            ->expectsOutput('Đã tạo quản trị viên.')
            ->assertSuccessful();

        $admin = User::query()->sole();
        $this->assertSame('admin@example.test', $admin->email);
        $this->assertSame('Quản trị mới', $admin->name);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);
    }

    #[DataProvider('invalidAdminCredentials')]
    public function test_invalid_interactive_credentials_create_no_account(
        string $email,
        string $password,
        string $confirmation
    ): void {
        $this->artisan('admin:create', ['email' => $email])
            ->expectsQuestion(
                'Mật khẩu (ít nhất 12 ký tự, chữ hoa/thường, số và ký hiệu)', $password
            )
            ->expectsQuestion('Nhập lại mật khẩu', $confirmation)
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidAdminCredentials(): array
    {
        return [
            'weak password' => ['admin@example.test', 'weak', 'weak'],
            'confirmation mismatch' => ['admin@example.test', 'PrivatePassword!123', 'AnotherPassword!123'],
            'invalid email' => ['invalid-email', 'PrivatePassword!123', 'PrivatePassword!123'],
        ];
    }

    public function test_admin_command_does_not_replace_an_existing_account(): void
    {
        $existing = User::factory()->sales()->create(['email' => 'existing@example.test']);
        $before = $existing->fresh()->getAttributes();
        $this->artisan('admin:create', ['email' => $existing->email])
            ->expectsQuestion(
                'Mật khẩu (ít nhất 12 ký tự, chữ hoa/thường, số và ký hiệu)', 'PrivatePassword!123'
            )
            ->expectsQuestion('Nhập lại mật khẩu', 'PrivatePassword!123')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($before, $existing->fresh()->getAttributes());
    }

    public function test_scheduler_publishes_every_due_chunk_and_preserves_scheduled_timestamp(): void
    {
        $this->freezeTime();
        $author = User::factory()->manager()->create();
        $due = Post::factory()->scheduled()->count(101)->create([
            'user_id' => $author->id, 'published_at' => now()->subHour(),
        ]);
        $atBoundary = Post::factory()->scheduled()->create([
            'user_id' => $author->id, 'published_at' => now(),
        ]);
        $future = Post::factory()->scheduled()->create([
            'user_id' => $author->id, 'published_at' => now()->addSecond(),
        ]);
        $missingDate = Post::factory()->scheduled()->create([
            'user_id' => $author->id, 'published_at' => null,
        ]);
        $draft = Post::factory()->create(['user_id' => $author->id, 'published_at' => now()->subDay()]);
        $originalDate = $due->first()->published_at->toDateTimeString();

        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('Đã xuất bản 102 bài viết.')->assertSuccessful();

        $this->assertSame(102, Post::query()->published()->count());
        $this->assertSame(PostStatus::Published, $atBoundary->fresh()->status);
        $this->assertSame($originalDate, $due->first()->fresh()->published_at->toDateTimeString());
        $this->assertSame(PostStatus::Scheduled, $future->fresh()->status);
        $this->assertSame(PostStatus::Scheduled, $missingDate->fresh()->status);
        $this->assertSame(PostStatus::Draft, $draft->fresh()->status);
        $this->artisan('posts:publish-scheduled')
            ->expectsOutput('Đã xuất bản 0 bài viết.')->assertSuccessful();
    }
}
