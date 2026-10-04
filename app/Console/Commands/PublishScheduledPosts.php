<?php

namespace App\Console\Commands;

use App\Actions\Posts\PublishPost;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Xuất bản bài viết đã đến lịch';

    public function handle(PublishPost $action): int
    {
        $count = 0;
        Post::query()->where('status', 'scheduled')->where('published_at', '<=', now())->chunkById(100,
            function ($posts) use ($action, &$count): void {
                foreach ($posts as $post) {
                    if ($action->handle($post)) {
                        $count++;
                    }
                }
            });
        $this->info("Đã xuất bản {$count} bài viết.");

        return self::SUCCESS;
    }
}
