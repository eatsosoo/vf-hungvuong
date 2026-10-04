<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_published_due_posts_appear_publicly_and_in_sitemap(): void
    {
        $this->freezeTime();
        $draft = Post::factory()->create();
        $scheduled = Post::factory()->scheduled()->create();
        $future = Post::factory()->published()->create(['published_at' => now()->addDay()]);
        $published = Post::factory()->published()->create();
        foreach ([$draft, $scheduled, $future] as $post) {
            $this->get(route('posts.show', $post->slug))->assertNotFound();
        }
        $this->get(route('posts.index'))->assertOk()->assertSee($published->title)->assertDontSee($draft->title);
        $this->get(route('sitemap.page',
            ['section' => 'posts',
                'page' => 1]))->assertOk()->assertSee($published->slug)->assertDontSee($draft->slug)
            ->assertDontSee($scheduled->slug)->assertDontSee($future->slug);
    }

    public function test_markdown_strips_html_and_unsafe_links_but_keeps_headings(): void
    {
        $post = Post::factory()->published()->create(['body' => '## Hướng '.
                "dẫn\n\n<script>alert(1)</script>\n\n[click](javascript:alert(2))\n\n<img ".
                'src=x onerror=alert(3)>']);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Hướng dẫn')
            ->assertOk()->assertSee('id="section-1"', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false)->assertDontSee('onerror=', false);
    }

    public function test_editor_cannot_publish_or_modify_another_authors_post(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create();
        $this->actingAs($editor)->put(route('admin.posts.update', $post),
            ['title' => 'Hacked', 'slug' => 'hacked', 'body' => 'Hello', 'status' => 'draft'])->assertForbidden();
        $this->post(route('admin.posts.store'),
            ['title' => 'Hacked', 'slug' => 'hacked', 'body' => 'Hello', 'status' => 'published'])->assertForbidden();
        $this->assertDatabaseMissing('posts', ['slug' => 'hacked']);
    }

    public function test_slug_changes_redirect_directly_to_latest_url_and_cannot_reuse_old_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->published()->create(['slug' => 'old-url']);
        $this->actingAs($admin)->put(route('admin.posts.update', $post),
            ['title' => 'New', 'slug' => 'new-url', 'body' => 'Hello', 'status' => 'published'])
            ->assertSessionHas('success');
        $this->get(route('posts.show', 'old-url'))->assertRedirect(route('posts.show', 'new-url'))->assertStatus(301);
        $this->put(route('admin.posts.update', $post),
            ['title' => 'Latest', 'slug' => 'latest-url', 'body' => 'Hello', 'status' => 'published']);
        $this->get(route('posts.show', 'old-url'))->assertRedirect(route('posts.show', 'latest-url'));
        $this->put(route('admin.posts.update', $post),
            ['title' => 'Bad',
                'slug' => 'old-url',
                'body' => 'Hello',
                'status' => 'published'])->assertSessionHasErrors('slug');
    }

    public function test_redirect_does_not_reveal_unpublished_post(): void
    {
        $post = Post::factory()->create();
        PostRedirect::factory()->create(['post_id' => $post->id, 'slug' => 'old-url']);
        $this->get(route('posts.show', 'old-url'))->assertNotFound();
    }

    public function test_scheduled_command_publishes_due_posts_once(): void
    {
        $this->freezeTime();
        $due = Post::factory()->scheduled()->create(['published_at' => now()->subMinute()]);
        $future = Post::factory()->scheduled()->create();
        $this->artisan('posts:publish-scheduled')->expectsOutput('Đã xuất bản 1 bài viết.')->assertSuccessful();
        $this->artisan('posts:publish-scheduled')->expectsOutput('Đã xuất bản 0 bài viết.')->assertSuccessful();
        $this->assertSame('published', $due->fresh()->status->value);
        $this->assertSame('scheduled', $future->fresh()->status->value);
    }

    public function test_editor_preview_is_private_and_noindex(): void
    {
        $editor = User::factory()->editor()->create();
        $own = Post::factory()->create(['user_id' => $editor->id]);
        $other = Post::factory()->create();
        $this->actingAs($editor)->get(route('admin.posts.preview', $own))->assertOk()->assertSee($own->title)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('admin.posts.preview', $other))->assertForbidden();
    }

    public function test_canonical_must_use_site_domain_and_slug_is_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['slug' => 'existing']);
        $this->actingAs($admin)->post(route('admin.posts.store'), ['title' => 'New', 'slug' => 'existing',
            'body' => 'Hello', 'status' => 'draft', 'canonical_url' => 'https://evil.example/path'])
            ->assertSessionHasErrors(['slug', 'canonical_url']);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_post_filters_and_relationships_are_saved(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $this->actingAs($admin)->post(route('admin.posts.store'), ['title' => 'Hướng dẫn VF', 'slug' => 'huong-dan-vf',
            'body' => 'Hello', 'status' => 'published', 'category_id' => $category->id,
            'tags' => [$tag->id], 'vehicles' => [$vehicle->id]])->assertRedirect();
        $post = Post::query()->sole();
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $tag->id]);
        $this->assertDatabaseHas('post_vehicle', ['post_id' => $post->id, 'vehicle_id' => $vehicle->id]);
        $this->get(route('posts.index',
            ['category' => $category->slug,
                'tag' => $tag->slug]))->assertOk()->assertSee($post->title);
        $this->get(route('posts.index', ['q' => "' OR 1=1 --"]))->assertDontSee($post->title);
    }

    public function test_bulk_updates_are_atomic_and_check_every_post(): void
    {
        $editor = User::factory()->editor()->create();
        $own = Post::factory()->create(['user_id' => $editor->id]);
        $other = Post::factory()->published()->create();
        $this->actingAs($editor)->post(route('admin.posts.bulk'),
            ['ids' => [$own->id, $other->id], 'operation' => 'draft'])->assertForbidden();
        $this->assertSame('published', $other->fresh()->status->value);
    }
}
