<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Support\Str;

class BuildPostSeo
{
    /**
     * @param  array{html: string, headings: array<int, array{id: string, text: string}>}  $content
     * @return array{
     *     seo: array{
     *         title: string, description: string, canonical: string, robots: string, ogType: string,
     *         image: ?string, imageAlt: ?string, structuredData: array<int, array<string, mixed>>
     *     },
     *     breadcrumbs: array<int, array{label: string, url?: string}>,
     *     readingMinutes: int
     * }
     */
    public function handle(Post $post, array $content, bool $preview = false): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(
            strip_tags(preg_replace('/<\/(?:h[1-6]|p|li|td|th|tr|blockquote)>/u', ' ', $content['html'])),
            ENT_QUOTES,
            'UTF-8',
        )));
        $description = $post->seo_description ?: $post->excerpt ?: Str::limit($text, 160);
        $canonical = $post->canonical_url ?: route((app()->getLocale() === 'en' ? 'en.' : '').'posts.show', $post->slug);
        $shareImage = $post->shareMedia ?? $post->media;
        $breadcrumbs = [
            ['label' => __('Trang chủ'), 'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'home')],
            ['label' => __('Góc tư vấn'), 'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'posts.index')],
        ];
        if ($post->category) {
            $breadcrumbs[] = [
                'label' => $post->category->name,
                'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'posts.index', ['category' => $post->category->slug]),
            ];
        }
        $breadcrumbs[] = ['label' => $post->title];
        $structuredData = [];
        if (! $preview) {
            $article = [
                '@type' => 'Article',
                '@id' => $canonical.'#article',
                'headline' => $post->title,
                'description' => $description,
                'inLanguage' => 'vi-VN',
                'datePublished' => $post->published_at->toIso8601String(),
                'dateModified' => $post->updated_at->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author->name],
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            ];
            if ($post->media) {
                $article['image'] = $post->media->url();
            }
            if ($post->category) {
                $article['articleSection'] = $post->category->name;
            }
            if ($post->tags->isNotEmpty()) {
                $article['keywords'] = $post->tags->pluck('name')->all();
            }
            $structuredData[] = $article;
            $structuredData[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(
                    fn (array $item, int $position): array => array_filter([
                        '@type' => 'ListItem',
                        'position' => $position + 1,
                        'name' => $item['label'],
                        'item' => $item['url'] ?? null,
                    ], fn (mixed $value): bool => $value !== null),
                    $breadcrumbs,
                    array_keys($breadcrumbs),
                ),
            ];
        }

        return [
            'seo' => [
                'title' => $post->seo_title ?: $post->title,
                'description' => $description,
                'canonical' => $canonical,
                'robots' => $preview ? 'noindex,nofollow' : 'index,follow',
                'ogType' => 'article',
                'image' => $shareImage?->url(),
                'imageAlt' => $shareImage ? ($shareImage->alt ?: $post->title) : null,
                'structuredData' => $structuredData,
            ],
            'breadcrumbs' => $breadcrumbs,
            'readingMinutes' => max(1, (int) ceil(count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY)) / 200)),
        ];
    }
}
