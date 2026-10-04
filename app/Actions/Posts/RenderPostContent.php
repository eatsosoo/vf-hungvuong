<?php

namespace App\Actions\Posts;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;

class RenderPostContent
{
    /** @return array{html: string, headings: array<int, array{id: string, text: string}>} */
    public function handle(string $markdown): array
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip', 'allow_unsafe_links' => false,
            'max_nesting_level' => 20, 'renderer' => ['soft_break' => "\n"],
        ]);
        $converter->getEnvironment()->addExtension(new TableExtension);
        $converter->getEnvironment()->addExtension(new StrikethroughExtension);
        $html = (string) $converter->convert($markdown);
        $html = preg_replace('/<h1>(.*?)<\/h1>/s', '<h2>$1</h2>', $html);

        $html = preg_replace_callback('/<p><a href="([^"]+)">video<\/a><\/p>/i',
            function (array $matches): string {
                $url = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                $parts = parse_url($url);
                if (! $parts || ($parts['scheme'] ?? '') !== 'https') {
                    return $matches[0];
                }
                $host = strtolower($parts['host'] ?? '');
                $videoId = null;
                if ($host === 'youtu.be') {
                    $videoId = ltrim($parts['path'] ?? '', '/');
                }
                if (in_array($host, ['youtube.com', 'www.youtube.com'], true) && ($parts['path'] ?? '') === '/watch') {
                    parse_str($parts['query'] ?? '', $query);
                    $videoId = $query['v'] ?? null;
                }
                if (! is_string($videoId) || ! preg_match('/\A[a-zA-Z0-9_-]{11}\z/', $videoId)) {
                    return $matches[0];
                }

                return '<div class="video-embed" data-youtube="'.$videoId.'">'
                    .'<button type="button" data-load-video>Phát video YouTube</button>'
                    .'<p>Video được tải từ YouTube khi bạn nhấn phát.</p></div>';
            }, $html);
        $headings = [];
        $html = preg_replace_callback('/<h([23])>(.*?)<\/h\1>/s',
            function (array $matches) use (&$headings): string {
                $id = 'section-'.(count($headings) + 1);
                $headings[] = ['id' => $id, 'text' => html_entity_decode(strip_tags($matches[2]), ENT_QUOTES, 'UTF-8')];

                return '<h'.$matches[1].' id="'.$id.'">'.$matches[2].'</h'.$matches[1].'>';
            }, $html);

        return ['html' => $html, 'headings' => $headings];
    }
}
