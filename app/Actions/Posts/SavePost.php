<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SavePost
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data, ?Post $post = null): Post
    {
        Gate::forUser($user)->authorize($post ? 'update' : 'create', $post ?? Post::class);
        if (($data['status'] ?? 'draft') !== 'draft') {
            Gate::forUser($user)->authorize('publish', Post::class);
        }

        return DB::transaction(function () use ($user, $data, $post): Post {
            $post = $post ? Post::query()->lockForUpdate()->findOrFail($post->id) : new Post;
            Gate::forUser($user)->authorize($post->exists ? 'update' : 'create', $post->exists ? $post : Post::class);
            $oldSlug = $post->slug;
            if (PostRedirect::query()->where('slug', $data['slug'])->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được dùng cho chuyển hướng.']);
            }
            $wasPublished = $post->exists && $post->status !== PostStatus::Draft;
            $tags = $data['tags'] ?? [];
            $vehicles = $data['vehicles'] ?? [];
            unset($data['tags'], $data['vehicles']);
            $data['user_id'] = $post->user_id ?? $user->id;
            if ($data['status'] === 'draft') {
                $data['published_at'] = null;
            }
            if ($data['status'] === 'published') {
                $data['published_at'] = $post->status === PostStatus::Published
                    ? ($post->published_at ?? now()) : now();
            }
            $post->fill($data)->save();
            $post->tags()->sync($tags);
            $post->vehicles()->sync($vehicles);
            if ($wasPublished && $oldSlug !== $post->slug) {
                PostRedirect::query()->firstOrCreate(['slug' => $oldSlug], ['post_id' => $post->id]);
            }

            return $post;
        });
    }
}
