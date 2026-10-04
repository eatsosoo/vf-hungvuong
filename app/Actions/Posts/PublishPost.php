<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class PublishPost
{
    public function handle(Post $post): bool
    {
        return DB::transaction(function () use ($post): bool {
            $post = Post::query()->lockForUpdate()->findOrFail($post->id);
            if ($post->status !== PostStatus::Scheduled || ! $post->published_at || $post->published_at->isFuture()) {
                return false;
            }
            $post->update(['status' => PostStatus::Published]);

            return true;
        });
    }
}
