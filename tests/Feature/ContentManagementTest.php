<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\NotifyNewLead;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_manager_creates_and_updates_promotion_with_vehicle_relations(): void
    {
        $this->freezeTime();
        $manager = User::factory()->manager()->create();
        $vehicle = Vehicle::factory()->create();
        $data = ['title' => 'Ưu đãi tháng này', 'slug' => 'uu-dai-thang', 'body' => 'Thông tin ưu đãi',
            'starts_at' => now()->subDay()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString(),
            'is_active' => '1', 'vehicles' => [$vehicle->id]];
        $this->actingAs($manager)->post(route('admin.promotions.store'), $data)->assertRedirect();
        $promotion = Promotion::query()->sole();
        $this->assertDatabaseHas('promotion_vehicle', ['promotion_id' => $promotion->id, 'vehicle_id' => $vehicle->id]);
        $this->put(route('admin.promotions.update', $promotion), [...$data, 'title' => 'Ưu đãi mới', 'vehicles' => []])
            ->assertSessionHas('success');
        $this->assertSame('Ưu đãi mới', $promotion->fresh()->title);
        $this->assertDatabaseCount('promotion_vehicle', 0);
        $this->delete(route('admin.promotions.destroy', $promotion))->assertRedirect();
        $this->assertModelMissing($promotion);
    }

    public function test_expired_and_hidden_promotions_are_not_public(): void
    {
        $this->freezeTime();
        $expired = Promotion::factory()->create(['ends_at' => now()->subHour()]);
        $hidden = Promotion::factory()->create(['is_active' => false]);
        $future = Promotion::factory()->create(['starts_at' => now()->addHour()]);
        foreach ([$expired, $hidden, $future] as $promotion) {
            $this->get(route('promotions.show', $promotion->slug))->assertNotFound();
            $this->get(route('promotions.index'))->assertDontSee($promotion->title);
        }
    }

    public function test_manager_creates_page_and_hidden_page_is_private(): void
    {
        $manager = User::factory()->manager()->create();
        $data = ['title' => 'FAQ', 'slug' => 'faq', 'body' => '## Hỏi đáp', 'is_active' => '0'];
        $this->actingAs($manager)->post(route('admin.pages.store'), $data)->assertRedirect();
        $page = Page::query()->sole();
        $this->get(route('pages.show', 'faq'))->assertNotFound();
        $this->put(route('admin.pages.update', $page), [...$data, 'is_active' => '1'])->assertSessionHas('success');
        $this->get(route('pages.show', 'faq'))->assertOk()->assertSee('Hỏi đáp');
        $this->delete(route('admin.pages.destroy', $page))->assertRedirect();
        $this->assertModelMissing($page);
    }

    public function test_editor_manages_categories_and_tags_without_dynamic_table_access(): void
    {
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor)->post(route('admin.taxonomies.store', 'categories'),
            ['name' => 'Đánh giá', 'slug' => 'danh-gia'])->assertSessionHas('success');
        $category = Category::query()->sole();
        $this->put(route('admin.taxonomies.update', ['categories', $category->id]),
            ['name' => 'Đánh giá xe', 'slug' => 'danh-gia-xe'])->assertSessionHas('success');
        $this->assertSame('Đánh giá xe', $category->fresh()->name);
        $this->post('/admin/taxonomies/users', ['name' => 'Attack', 'slug' => 'attack'])->assertNotFound();
        $this->post(route('admin.taxonomies.store',
            'tags'),
            ['name' => 'VF7',
                'slug' => 'vf7'])->assertSessionHas('success');
        $tag = Tag::query()->sole();
        $this->delete(route('admin.taxonomies.destroy', ['tags', $tag->id]))->assertSessionHas('success');
        $this->assertModelMissing($tag);
        $this->delete(route('admin.taxonomies.destroy', ['categories', $category->id]))->assertSessionHas('success');
        $this->assertModelMissing($category);
    }

    public function test_account_requires_strong_confirmed_password(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Staff', 'email' => 'staff@example.com',
            'role' => 'sales', 'is_active' => '1', 'password' => 'weak', 'password_confirmation' => 'weak'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_change_requires_current_password_and_logs_out(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.password.update'), ['current_password' => 'wrong',
            'password' => 'StrongPassword!123', 'password_confirmation' => 'StrongPassword!123'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.password.update'), ['current_password' => 'password',
            'password' => 'StrongPassword!123', 'password_confirmation' => 'StrongPassword!123'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('StrongPassword!123', $admin->fresh()->password));
    }

    public function test_account_disable_revokes_existing_database_sessions(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->sales()->create();
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $sales->id,
            'payload' => 'test', 'last_activity' => time()]);
        $this->actingAs($admin)->put(route('admin.users.update', $sales), ['name' => $sales->name,
            'email' => $sales->email, 'role' => 'sales', 'is_active' => '0'])->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertFalse($sales->fresh()->is_active);
    }

    public function test_new_lead_notification_contains_no_personal_data_and_respects_setting(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $lead = Lead::factory()->create();
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '1']);
        (new NotifyNewLead($lead->id))->handle();
        Notification::assertSentTo($admin, NewLeadNotification::class,
            fn ($notification) => $notification->toArray($admin) === ['lead_id' => $lead->id,
                'type' => 'consultation']);
        Notification::assertNotSentTo($editor, NewLeadNotification::class);
    }

    public function test_disabled_notifications_send_nothing(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $lead = Lead::factory()->create();
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '0']);
        (new NotifyNewLead($lead->id))->handle();
        Notification::assertNothingSent();
    }

    public function test_lead_rate_limit_stops_repeated_submissions(): void
    {
        Queue::fake([NotifyNewLead::class]);
        $data = ['type' => 'consultation', 'name' => 'An', 'phone' => '0901234567', 'consent' => '1'];
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('leads.store'), $data)->assertSessionHas('success');
        }
        $this->post(route('leads.store'), $data)->assertTooManyRequests();
        $this->assertDatabaseCount('leads', 3);
    }

    public function test_editing_published_post_preserves_original_publication_date(): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->published()->create(['published_at' => now()->subDays(10)]);
        $before = $post->published_at->toDateTimeString();
        $this->actingAs($admin)->put(route('admin.posts.update', $post), ['title' => 'Updated', 'slug' => $post->slug,
            'body' => 'Nội dung mới', 'status' => 'published'])->assertSessionHas('success');
        $this->assertSame($before, $post->fresh()->published_at->toDateTimeString());
    }

    public function test_article_table_and_structured_data_are_safe(): void
    {
        $post = Post::factory()->published()->create(['title' => '</script><script>alert(1)</script>',
            'body' => "| Xe | Số chỗ |\n| --- | --- |\n| VF7 | 5 |"]);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('<table>', false)
            ->assertSee('application/ld+json', false)->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_invalid_sitemap_shards_and_spoofed_hosts_are_rejected(): void
    {
        $this->get(route('sitemap'))->assertOk()->assertSee('sitemapindex');
        $this->get(route('sitemap.page', ['section' => 'posts', 'page' => 999]))->assertNotFound();
        $this->app['env'] = 'production';
        $this->get('https://evil.example/')->assertStatus(400);
        Request::setTrustedHosts([]);
    }

    /** @return array<string, array{string, bool, bool, bool}> */
    public static function rolePermissions(): array
    {
        return ['admin' => ['admin', true, true, true], 'manager' => ['manager', true, true, false],
            'sales' => ['sales', false, false, false], 'editor' => ['editor', true, false, false]];
    }

    #[DataProvider('rolePermissions')]
    public function test_role_permission_matrix(string $role, bool $content, bool $catalog, bool $accounts): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->assertSame($content, Gate::forUser($user)->allows('manage-content'));
        $this->assertSame($catalog, Gate::forUser($user)->allows('manage-catalog'));
        $this->assertSame($accounts, Gate::forUser($user)->allows('manage-users'));
        $this->assertSame($catalog, Gate::forUser($user)->allows('publish', Post::class));
    }

    public function test_test_drive_confirmation_requires_appointment_and_valid_status(): void
    {
        $this->freezeTime();
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create(['type' => 'test_drive']);
        $this->actingAs($manager)->put(route('admin.leads.update', $lead), ['status' => 'confirmed'])
            ->assertSessionHasErrors('appointment_at');
        $this->put(route('admin.leads.update', $lead), ['status' => 'won'])->assertSessionHasErrors('status');
        $this->put(route('admin.leads.update', $lead), ['status' => 'confirmed',
            'appointment_at' => now()->addDay()->format('Y-m-d\TH:i'), 'location' => 'Đại lý'])
            ->assertSessionHas('success');
        $this->assertSame('confirmed', $lead->fresh()->status->value);
    }

    public function test_historical_appointment_can_be_completed_without_changing_date(): void
    {
        $this->freezeTime();
        $manager = User::factory()->manager()->create();
        $date = now()->subDay()->format('Y-m-d\TH:i');
        $lead = Lead::factory()->create(['type' => 'test_drive', 'status' => 'confirmed', 'appointment_at' => $date]);
        $this->actingAs($manager)->put(route('admin.leads.update', $lead),
            ['status' => 'completed', 'appointment_at' => $date, 'note' => 'Khách đã lái thử']
        )->assertSessionHas('success');
        $this->assertSame('completed', $lead->fresh()->status->value);
    }

    public function test_notification_retry_does_not_duplicate_existing_delivery(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->create();
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '1']);
        $job = new NotifyNewLead($lead->id);
        $job->handle();
        $job->handle();
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_vehicle_specs_reject_scalar_json(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.vehicles.store'),
            ['name' => 'VF7', 'slug' => 'vf7', 'is_active' => '1', 'specifications' => 'true'])
            ->assertSessionHasErrors('specifications');
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_production_rejects_plain_http_and_errors_keep_security_headers(): void
    {
        $this->app['env'] = 'production';
        $this->get('http://localhost/dang-nhap')->assertForbidden()
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        Request::setTrustedHosts([]);
    }

    public function test_editor_textarea_preserves_markdown_exactly(): void
    {
        $editor = User::factory()->editor()->create();
        $body = "## Heading\n\nBody **bold**.";
        $post = Post::factory()->create(['user_id' => $editor->id, 'body' => $body]);
        $response = $this->actingAs($editor)->get(route('admin.posts.edit', $post));
        $response->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame($body, $xpath->query('//textarea[@name="body"]')->item(0)->textContent);
    }

    public function test_seeded_catalog_and_articles_do_not_create_active_default_accounts(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(0, User::query()->where('is_active', true)->count());
        $privilegedUsers = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Manager, UserRole::Sales])
            ->count();
        $this->assertSame(0, $privilegedUsers);
        $author = User::query()->sole();
        $this->assertSame(UserRole::Editor, $author->role);
        $this->assertFalse($author->is_active);
        $this->assertSame('sample-editor@example.invalid', $author->email);
        $this->assertTrue(Hash::isHashed($author->password));
        $this->assertFalse(Hash::check('password', $author->password));
        $this->assertDatabaseCount('posts', 20);
        $this->assertSame(20, Post::published()->count());
        $this->assertEqualsCanonicalizing(['vf5', 'vf6', 'vf7', 'vf8', 'vf9'],
            Vehicle::query()->pluck('slug')->all());
        foreach (['gioi-thieu', 'faq', 'bao-mat', 'dieu-khoan'] as $slug) {
            $this->get(route('pages.show', $slug))->assertOk();
        }
        $this->get(route('home'))->assertOk()->assertSee('VinFast VF 7');
    }

    public function test_video_embeds_only_accept_youtube_ids_and_wait_for_visitor_action(): void
    {
        $post = Post::factory()->published()->create([
            'body' => "[video](https://www.youtube.com/watch?v=abcdefghijk)\n\n"
                .'[video](https://evil.example/watch?v=abcdefghijk)',
        ]);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('data-youtube="abcdefghijk"', false)
            ->assertSee('Phát video YouTube')->assertDontSee('<iframe', false)
            ->assertDontSee('data-youtube="https://evil.example', false);
    }
}
