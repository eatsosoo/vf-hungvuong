<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SamplePostSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Sample articles are only seeded in local or testing environments.');

            return;
        }

        /** @var list<array{slug: string, title: string, seo_title: string, seo_description: string,
         * excerpt: string, focus_keyword: string, category: string, vehicles: list<string>,
         * tags: list<string>}> $samples
         */
        $samples = json_decode(File::get(database_path('seeders/content/posts/index.json')),
            true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($samples): void {
            $author = User::query()->where('is_active', true)
                ->whereIn('role', [UserRole::Admin->value, UserRole::Manager->value])->orderBy('id')->first();
            $author ??= User::query()->firstOrCreate(['email' => 'sample-editor@example.invalid'], [
                'name' => 'Ban biên tập VinFast Hùng Vương',
                'password' => Str::random(64),
                'role' => UserRole::Editor,
                'is_active' => false,
            ]);

            foreach ($samples as $index => $sample) {
                $this->seedPost($sample, $author, $index);
            }
        });
    }

    /**
     * @param array{slug: string, title: string, seo_title: string, seo_description: string,
     * excerpt: string, focus_keyword: string, category: string, vehicles: list<string>, tags: list<string>} $sample
     */
    private function seedPost(array $sample, User $author, int $index): void
    {
        if (Post::query()->where('slug', $sample['slug'])->exists()) {
            return;
        }

        $categories = ['danh-gia-xe' => 'Đánh giá xe', 'so-sanh-xe' => 'So sánh xe',
            'kinh-nghiem-su-dung' => 'Kinh nghiệm sử dụng'];
        $category = Category::query()->firstOrCreate(['slug' => $sample['category']], [
            'name' => $categories[$sample['category']],
        ]);
        $vehicles = Vehicle::query()->whereIn('slug', $sample['vehicles'])->with('media')->get()->keyBy('slug');
        $preferredSlug = $sample['vehicles'][$index % count($sample['vehicles'])];
        $coverId = $vehicles->get($preferredSlug)?->media?->id
            ?? collect($sample['vehicles'])->map(fn (string $slug): ?int => $vehicles->get($slug)?->media?->id)
                ->filter()->first();
        $body = File::get(database_path('seeders/content/posts/'.$sample['slug'].'.md'));
        $body = preg_replace_callback('/\{\{(vehicle|post|contact|vehicles):([a-z0-9_-]+)\}\}/',
            fn (array $match): string => match ($match[1]) {
                'vehicle' => route('vehicles.show', $match[2], false),
                'post' => route('posts.show', $match[2], false),
                'contact' => route('contact', ['type' => $match[2]], false),
                'vehicles' => route('vehicles.index', [], false),
            }, $body);

        $post = Post::query()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'media_id' => $coverId,
            'share_media_id' => $coverId,
            'title' => $sample['title'],
            'slug' => $sample['slug'],
            'excerpt' => $sample['excerpt'],
            'body' => $body,
            'status' => PostStatus::Published,
            'published_at' => now()->subDays(20 - $index),
            'seo_title' => $sample['seo_title'],
            'seo_description' => $sample['seo_description'],
            'focus_keyword' => $sample['focus_keyword'],
        ]);
        $post->vehicles()->sync($vehicles->modelKeys());
        $tagIds = collect($sample['tags'])->map(fn (string $name): int => Tag::query()->firstOrCreate([
            'slug' => Str::slug($name),
        ], ['name' => $name])->id);
        $post->tags()->sync($tagIds);
    }
}
