<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminMutationPermissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[DataProvider('restrictedCatalogRoles')]
    /** @param class-string<Model> $modelClass */
    public function test_restricted_roles_cannot_create_update_or_delete_catalog_records(
        string $role,
        string $resource,
        string $modelClass,
    ): void {
        $user = User::factory()->create(['role' => $role]);
        $record = $modelClass::factory()->create();
        $before = $record->fresh()->getAttributes();
        $this->actingAs($user);

        $this->get(route('admin.'.$resource.'.edit', $record))->assertForbidden();
        $this->post(route('admin.'.$resource.'.store'), [])->assertForbidden();
        $this->put(route('admin.'.$resource.'.update', $record), [])->assertForbidden();
        $this->delete(route('admin.'.$resource.'.destroy', $record))->assertForbidden();

        $this->assertSame($before, $record->fresh()->getAttributes());
        $this->assertDatabaseCount($record->getTable(), 1);
    }

    /** @return array<string, array{string, string, class-string<Model>}> */
    public static function restrictedCatalogRoles(): array
    {
        $cases = [];
        $models = ['vehicles' => Vehicle::class, 'promotions' => Promotion::class, 'pages' => Page::class];
        foreach (['sales', 'editor'] as $role) {
            foreach ($models as $resource => $modelClass) {
                $cases[$role.' '.$resource] = [$role, $resource, $modelClass];
            }
        }

        return $cases;
    }

    #[DataProvider('restrictedAccountRoles')]
    public function test_only_admin_can_create_or_update_accounts(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $target = User::factory()->sales()->create();
        $previousPassword = $target->password;

        $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'Unauthorized account', 'email' => 'unauthorized@example.test',
            'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123',
            'role' => 'admin', 'is_active' => true,
        ])->assertForbidden();
        $this->put(route('admin.users.update', $target), [
            'name' => 'Unauthorized change', 'email' => $target->email, 'role' => 'admin', 'is_active' => false,
        ])->assertForbidden();

        $this->assertSame('sales', $target->fresh()->role->value);
        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame($previousPassword, $target->fresh()->password);
        $this->assertDatabaseCount('users', 2);
    }

    /** @return array<string, array{string}> */
    public static function restrictedAccountRoles(): array
    {
        return ['manager' => ['manager'], 'sales' => ['sales'], 'editor' => ['editor']];
    }

    #[DataProvider('taxonomyKinds')]
    /** @param class-string<Model> $modelClass */
    public function test_sales_cannot_mutate_categories_or_tags(string $kind, string $modelClass): void
    {
        $sales = User::factory()->sales()->create();
        $record = $modelClass::factory()->create();
        $before = $record->fresh()->getAttributes();

        $this->actingAs($sales)->post(route('admin.taxonomies.store', $kind), [
            'name' => 'Unauthorized taxonomy', 'slug' => 'unauthorized-taxonomy',
        ])->assertForbidden();
        $this->put(route('admin.taxonomies.update', [$kind, $record->id]), [
            'name' => 'Unauthorized change', 'slug' => 'unauthorized-change',
        ])->assertForbidden();
        $this->delete(route('admin.taxonomies.destroy', [$kind, $record->id]))->assertForbidden();

        $this->assertSame($before, $record->fresh()->getAttributes());
        $this->assertDatabaseCount($kind, 1);
    }

    /** @return array<string, array{string, class-string<Model>}> */
    public static function taxonomyKinds(): array
    {
        return ['categories' => ['categories', Category::class], 'tags' => ['tags', Tag::class]];
    }

    #[DataProvider('nonDraftStatuses')]
    public function test_editor_can_preview_but_cannot_change_their_non_draft_post(string $status): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create([
            'user_id' => $editor->id, 'status' => $status, 'published_at' => now()->addDay(),
        ]);

        $this->actingAs($editor)->get(route('admin.posts.preview', $post))->assertOk();
        $this->get(route('admin.posts.edit', $post))->assertForbidden();
        $this->put(route('admin.posts.update', $post), [
            'title' => $post->title, 'slug' => $post->slug, 'body' => 'Unauthorized update', 'status' => 'draft',
        ])->assertForbidden();
        $this->delete(route('admin.posts.destroy', $post))->assertForbidden();

        $this->assertSame($status, $post->fresh()->status->value);
        $this->assertNotSame('Unauthorized update', $post->fresh()->body);
    }

    /** @return array<string, array{string}> */
    public static function nonDraftStatuses(): array
    {
        return ['published' => ['published'], 'scheduled' => ['scheduled']];
    }

    public function test_editor_cannot_delete_their_own_draft_individually_or_in_bulk(): void
    {
        $editor = User::factory()->editor()->create();
        $posts = Post::factory()->count(2)->create(['user_id' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.posts.destroy', $posts->first()))->assertForbidden();
        $this->post(route('admin.posts.bulk'), [
            'ids' => $posts->modelKeys(), 'operation' => 'delete',
        ])->assertForbidden();

        $this->assertDatabaseCount('posts', 2);
        $this->assertModelExists($posts->first());
        $this->assertModelExists($posts->last());
    }

    public function test_sales_cannot_preview_or_mutate_posts_even_when_they_are_the_author(): void
    {
        $sales = User::factory()->sales()->create();
        $post = Post::factory()->create(['user_id' => $sales->id]);
        $data = ['title' => 'Unauthorized article', 'slug' => 'unauthorized-article',
            'body' => 'Unauthorized update', 'status' => 'draft'];

        $this->actingAs($sales)->get(route('admin.posts.preview', $post))->assertForbidden();
        $this->post(route('admin.posts.store'), $data)->assertForbidden();
        $this->put(route('admin.posts.update', $post), $data)->assertForbidden();
        $this->delete(route('admin.posts.destroy', $post))->assertForbidden();
        $this->post(route('admin.posts.bulk'), ['ids' => [$post->id], 'operation' => 'draft'])->assertForbidden();

        $this->assertDatabaseCount('posts', 1);
        $this->assertNotSame('Unauthorized update', $post->fresh()->body);
    }
}
