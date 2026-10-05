<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthAccountLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_regenerates_session_and_honors_the_intended_admin_destination(): void
    {
        $admin = User::factory()->admin()->create();
        $destination = route('admin.users.index');
        $this->withSession(['url.intended' => $destination, 'marker' => 'preserved']);
        $previousSessionId = session()->getId();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect($destination)->assertSessionHas('marker', 'preserved');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_logout_discards_session_data_and_rotates_the_csrf_token(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->withSession(['marker' => 'private-data', '_token' => 'previous-token']);
        $previousSessionId = session()->getId();

        $this->post(route('admin.logout'))->assertRedirect(route('login'))->assertSessionMissing('marker');

        $this->assertGuest();
        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertNotSame('previous-token', session()->token());
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_account_update_without_a_new_password_preserves_credentials_and_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->sales()->create();
        $previousPassword = $account->password;
        $previousRememberToken = $account->remember_token;
        $this->storeSession('account-session', $account);

        $this->actingAs($admin)->put(route('admin.users.update', $account), [
            'name' => 'Updated staff', 'email' => $account->email, 'role' => 'sales', 'is_active' => true,
            'password' => '', 'password_confirmation' => '',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $account->refresh();
        $this->assertSame('Updated staff', $account->name);
        $this->assertSame($previousPassword, $account->password);
        $this->assertSame($previousRememberToken, $account->remember_token);
        $this->assertDatabaseHas('sessions', ['id' => 'account-session', 'user_id' => $account->id]);
    }

    public function test_admin_password_reset_revokes_only_the_target_accounts_sessions_and_remember_token(): void
    {
        $admin = User::factory()->admin()->create();
        $account = User::factory()->sales()->create();
        $this->storeSession('target-session', $account);
        $this->storeSession('admin-session', $admin);

        $this->actingAs($admin)->put(route('admin.users.update', $account), [
            'name' => $account->name, 'email' => $account->email, 'role' => 'sales', 'is_active' => true,
            'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $account->refresh();
        $this->assertTrue(Hash::check('NewPassword!123', $account->password));
        $this->assertNull($account->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session', 'user_id' => $admin->id]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_password_change_revokes_other_devices_without_deleting_another_users_sessions(): void
    {
        $account = User::factory()->sales()->create();
        $other = User::factory()->sales()->create();
        $this->storeSession('own-device', $account);
        $this->storeSession('other-device', $other);

        $this->actingAs($account)->put(route('admin.password.update'), [
            'current_password' => 'password',
            'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => 'own-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-device', 'user_id' => $other->id]);
    }

    #[DataProvider('invalidNewPasswords')]
    public function test_rejected_password_changes_leave_authentication_credentials_and_sessions_intact(
        string $password,
        string $confirmation,
    ): void {
        $account = User::factory()->sales()->create();
        $previousPassword = $account->password;
        $this->storeSession('existing-device', $account);

        $this->actingAs($account)->put(route('admin.password.update'), [
            'current_password' => 'password', 'password' => $password, 'password_confirmation' => $confirmation,
        ])->assertSessionHasErrors('password');

        $this->assertAuthenticatedAs($account);
        $this->assertSame($previousPassword, $account->fresh()->password);
        $this->assertDatabaseHas('sessions', ['id' => 'existing-device', 'user_id' => $account->id]);
    }

    /** @return array<string, array{string, string}> */
    public static function invalidNewPasswords(): array
    {
        return ['weak password' => ['weak', 'weak'], 'mismatched confirmation' => ['NewPassword!123', 'Different!123']];
    }

    #[DataProvider('lastAdminChanges')]
    public function test_last_active_admin_cannot_be_demoted_or_disabled_separately(string $role, bool $active): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->inactive()->create();
        $this->storeSession('last-admin-session', $admin);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => $role, 'is_active' => $active,
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertDatabaseHas('sessions', ['id' => 'last-admin-session']);
    }

    /** @return array<string, array{string, bool}> */
    public static function lastAdminChanges(): array
    {
        return ['demotion' => ['manager', true], 'deactivation' => ['admin', false]];
    }

    public function test_another_active_admin_allows_deactivation_and_clears_the_targets_remember_token(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        $this->storeSession('target-device', $target);
        $this->storeSession('remaining-admin-device', $admin);

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => $target->email, 'role' => 'manager', 'is_active' => false,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertFalse($target->fresh()->is_active);
        $this->assertSame(UserRole::Manager, $target->fresh()->role);
        $this->assertNull($target->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-device']);
        $this->assertDatabaseHas('sessions', ['id' => 'remaining-admin-device']);
    }

    private function storeSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'payload' => 'test-session', 'last_activity' => time(),
        ]);
    }
}
