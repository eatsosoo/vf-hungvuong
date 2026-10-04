<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BulkUpdatePosts
{
    /** @param array{ids: array<int, int>, operation: string} $data */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $posts = Post::query()->whereIn('id', $data['ids'])->lockForUpdate()->get();
            foreach ($posts as $post) {
                Gate::forUser($user)->authorize($data['operation'] === 'delete' ? 'delete' : 'update', $post);
            }
            foreach ($posts as $post) {
                if ($data['operation'] === 'delete') {
                    $post->delete();
                } else {
                    $post->update(['status' => 'draft', 'published_at' => null]);
                }
            }
        });
    }
}
