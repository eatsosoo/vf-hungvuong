<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostAdminLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_editor_draft_creation_uses_the_authenticated_author_and_clears_a_publication_date(): void
    {
        $this->freezeTime();
        $editor = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Draft written by editor', 'slug' => 'draft-written-by-editor', 'body' => 'Draft content',
            'status' => 'draft', 'published_at' => now()->addDay()->toDateTimeString(), 'user_id' => $other->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $post = Post::query()->sole();
        $this->assertSame($editor->id, $post->user_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_manager_edits_preserve_the_author_and_replace_or_clear_tags_and_vehicles(): void
    {
        $manager = User::factory()->manager()->create();
        $author = User::factory()->editor()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $oldVehicle = Vehicle::factory()->create();
        $newVehicle = Vehicle::factory()->create();
        $post->tags()->attach($oldTag);
        $post->vehicles()->attach($oldVehicle);
        $data = ['title' => $post->title, 'slug' => $post->slug, 'body' => 'Updated content', 'status' => 'draft'];

        $this->actingAs($manager)->put(route('admin.posts.update', $post), [
            ...$data, 'user_id' => $manager->id, 'tags' => [$newTag->id], 'vehicles' => [$newVehicle->id],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame($author->id, $post->fresh()->user_id);
        $this->assertSame([$newTag->id], $post->tags()->pluck('tags.id')->all());
        $this->assertSame([$newVehicle->id], $post->vehicles()->pluck('vehicles.id')->all());

        $this->put(route('admin.posts.update', $post), $data)->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(0, $post->tags()->count());
        $this->assertSame(0, $post->vehicles()->count());
        $this->assertModelExists($oldTag);
        $this->assertModelExists($oldVehicle);
    }

    public function test_manager_can_schedule_publish_immediately_and_withdraw_the_same_article(): void
    {
        $this->freezeTime();
        $manager = User::factory()->manager()->create();
        $scheduledAt = now()->addDays(2);
        $data = ['title' => 'Scheduled article', 'slug' => 'scheduled-article', 'body' => 'Scheduled content'];

        $this->actingAs($manager)->post(route('admin.posts.store'), [
            ...$data, 'status' => 'scheduled', 'published_at' => $scheduledAt->toDateTimeString(),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $post = Post::query()->sole();
        $this->assertSame(PostStatus::Scheduled, $post->status);
        $this->assertSame($scheduledAt->toDateTimeString(), $post->published_at->toDateTimeString());
        $this->get(route('posts.show', $post->slug))->assertNotFound();

        $this->put(route('admin.posts.update', $post), [...$data, 'status' => 'published'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertSame(now()->toDateTimeString(), $post->fresh()->published_at->toDateTimeString());
        $this->get(route('posts.show', $post->slug))->assertOk();

        $this->put(route('admin.posts.update', $post), [...$data, 'status' => 'draft'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(PostStatus::Draft, $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
    }

    #[DataProvider('invalidScheduleDates')]
    public function test_invalid_schedule_dates_leave_the_existing_draft_and_relations_unchanged(?string $date): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->setTime(10, 0));
        $manager = User::factory()->manager()->create();
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        $this->actingAs($manager)->put(route('admin.posts.update', $post), [
            'title' => 'Rejected update', 'slug' => $post->slug, 'body' => 'Rejected content',
            'status' => 'scheduled', 'published_at' => $date, 'tags' => [],
        ])->assertSessionHasErrors('published_at');

        $this->assertSame($post->title, $post->fresh()->title);
        $this->assertSame(PostStatus::Draft, $post->fresh()->status);
        $this->assertNull($post->fresh()->published_at);
        $this->assertSame([$tag->id], $post->tags()->pluck('tags.id')->all());
    }

    /** @return array<string, array{?string}> */
    public static function invalidScheduleDates(): array
    {
        return ['missing' => [null], 'past' => ['2026-10-04 10:00:00'], 'current time' => ['2026-10-05 10:00:00']];
    }

    public function test_bulk_withdrawal_clears_publication_dates_only_for_selected_posts(): void
    {
        $manager = User::factory()->manager()->create();
        $selected = Post::factory()->published()->count(2)->create();
        $unselected = Post::factory()->published()->create();
        $tag = Tag::factory()->create();
        $selected->first()->tags()->attach($tag);

        $this->actingAs($manager)->post(route('admin.posts.bulk'), [
            'ids' => $selected->modelKeys(), 'operation' => 'draft',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        foreach ($selected as $post) {
            $this->assertSame(PostStatus::Draft, $post->fresh()->status);
            $this->assertNull($post->fresh()->published_at);
        }
        $this->assertSame(PostStatus::Published, $unselected->fresh()->status);
        $this->assertNotNull($unselected->fresh()->published_at);
        $this->assertSame([$tag->id], $selected->first()->tags()->pluck('tags.id')->all());
    }

    #[DataProvider('deletionMethods')]
    public function test_article_deletion_removes_redirects_and_pivots_but_keeps_reusable_records(bool $bulk): void
    {
        $manager = User::factory()->manager()->create();
        $media = Media::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create(['media_id' => $media->id, 'category_id' => $category->id]);
        $redirect = PostRedirect::factory()->create(['post_id' => $post->id]);
        $tag = Tag::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $post->tags()->attach($tag);
        $post->vehicles()->attach($vehicle);
        $otherPost = Post::factory()->create();
        $this->actingAs($manager);

        $response = $bulk
            ? $this->post(route('admin.posts.bulk'), ['ids' => [$post->id], 'operation' => 'delete'])
            : $this->delete(route('admin.posts.destroy', $post));
        $response->assertRedirect()->assertSessionHas('success');

        $this->assertModelMissing($post);
        $this->assertModelMissing($redirect);
        $this->assertDatabaseCount('post_tag', 0);
        $this->assertDatabaseCount('post_vehicle', 0);
        foreach ([$media, $category, $tag, $vehicle, $otherPost] as $record) {
            $this->assertModelExists($record);
        }
    }

    /** @return array<string, array{bool}> */
    public static function deletionMethods(): array
    {
        return ['individual' => [false], 'bulk' => [true]];
    }

    #[DataProvider('invalidBulkSelections')]
    public function test_invalid_bulk_operations_cannot_change_any_selected_post(string $case, string $error): void
    {
        $manager = User::factory()->manager()->create();
        $posts = Post::factory()->published()->count(2)->create();
        $data = match ($case) {
            'duplicate' => ['ids' => [$posts->first()->id, $posts->first()->id], 'operation' => 'delete'],
            'unknown' => ['ids' => [$posts->first()->id, 999999], 'operation' => 'delete'],
            default => ['ids' => $posts->modelKeys(), 'operation' => 'publish'],
        };

        $this->actingAs($manager)->post(route('admin.posts.bulk'), $data)->assertSessionHasErrors($error);

        $this->assertDatabaseCount('posts', 2);
        foreach ($posts as $post) {
            $this->assertSame(PostStatus::Published, $post->fresh()->status);
            $this->assertNotNull($post->fresh()->published_at);
        }
    }

    /** @return array<string, array{string, string}> */
    public static function invalidBulkSelections(): array
    {
        return ['duplicate IDs' => ['duplicate', 'ids.1'], 'missing post' => ['unknown', 'ids.1'],
            'unavailable operation' => ['operation', 'operation']];
    }
}
