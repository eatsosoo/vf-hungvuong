<?php

namespace App\Actions\Posts;

use App\Models\Media;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class AssessPostSeo
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array{html: string, headings: array<int, array{id: string, text: string}>}  $content
     * @return array{
     *     score: int, label: string, wordCount: int, readingMinutes: int,
     *     checks: array<int, array{key: string, label: string, passed: bool, points: int, max: int, advice: string}>
     * }
     */
    public function handle(array $data, array $content, ?Media $cover = null): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"><main>'.$content['html'].'</main>');
        $xpath = new DOMXPath($document);
        $text = $this->plainText($document->textContent);
        $wordCount = $this->wordCount($text);
        $title = trim($data['seo_title'] ?? '') ?: trim($data['title'] ?? '');
        $description = trim($data['seo_description'] ?? '');
        $keyword = $this->normalise($data['focus_keyword'] ?? '');
        $intro = $xpath->query('//main//p')->item(0)?->textContent ?? '';
        $slug = $data['slug'] ?? '';
        $paragraphs = iterator_to_array($xpath->query('//main//p'));
        $readable = $paragraphs !== [] && collect($paragraphs)->every(
            fn ($paragraph): bool => $this->wordCount($paragraph->textContent) <= 120,
        );
        $images = iterator_to_array($xpath->query('//main//img'));
        $imageAlt = $images !== [] && collect($images)->every(
            fn ($image): bool => trim($image->getAttribute('alt')) !== '',
        );
        $siteHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        $internalLinks = 0;
        $externalLinks = 0;
        foreach ($xpath->query('//main//a[@href]') as $link) {
            $url = $link->getAttribute('href');
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                $internalLinks++;
            } elseif (in_array($scheme, ['http', 'https'], true)) {
                if ($host === $siteHost) {
                    $internalLinks++;
                } else {
                    $externalLinks++;
                }
            }
        }
        $checks = [
            $this->check('title', 'Tiêu đề rõ ràng', 10, Str::length($title) >= 20 && Str::length($title) <= 65,
                'Gợi ý tiêu đề 20–65 ký tự, mô tả đúng nội dung; không nhồi từ khóa.'),
            $this->check('description', 'Mô tả SEO', 10,
                Str::length($description) >= 100 && Str::length($description) <= 160,
                'Viết mô tả riêng khoảng 100–160 ký tự, cho người đọc biết giá trị của bài.'),
            $this->check('slug', 'Đường dẫn dễ đọc', 5,
                preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) === 1 && Str::length($slug) <= 80,
                'Dùng slug ngắn, có nghĩa, chữ không dấu và dấu gạch nối.'),
            $this->check('keyword', 'Chủ đề chính', 5, $keyword !== '',
                'Chọn một cụm từ thể hiện ý định tìm kiếm chính của bài viết.'),
            $this->check('keyword-title', 'Chủ đề trong tiêu đề', 10,
                $keyword !== '' && str_contains($this->normalise($title), $keyword),
                'Đưa cụm từ chính vào tiêu đề một cách tự nhiên nếu phù hợp.'),
            $this->check('keyword-intro', 'Mở bài đúng trọng tâm', 5,
                $keyword !== '' && str_contains($this->normalise($intro), $keyword),
                'Mở bài nói rõ chủ đề và câu hỏi bài viết sẽ giải đáp.'),
            $this->check('headings', 'Bố cục có tiêu đề phụ', 10, count($content['headings']) >= 2,
                'Chia nội dung thành ít nhất hai mục H2/H3 có ý nghĩa; tiêu đề bài là H1 tự động.'),
            $this->check('paragraphs', 'Đoạn văn dễ đọc', 5, $readable,
                'Chia đoạn dài hơn 120 từ thành các đoạn ngắn khi điều đó giúp đọc dễ hơn.'),
            $this->check('cover', 'Ảnh đại diện có mô tả', 10, $cover !== null && trim($cover->alt ?? '') !== '',
                'Chọn ảnh đại diện liên quan và thêm mô tả alt trong thư viện ảnh.'),
            $this->check('image-alt', 'Ảnh trong bài có alt', 5, $imageAlt,
                'Chèn ảnh hữu ích kèm mô tả nội dung ảnh, tránh lặp từ khóa máy móc.'),
            $this->check('internal-links', 'Liên kết nội bộ', 5, $internalLinks > 0,
                'Liên kết đến mẫu xe, bài liên quan hoặc trang tư vấn đúng ngữ cảnh.'),
            $this->check('sources', 'Liên kết nguồn tham khảo', 5, $externalLinks > 0,
                'Dẫn nguồn đáng tin cậy cho thông số và thông tin cần kiểm chứng.'),
            $this->check('depth', 'Độ sâu nội dung', 10, $wordCount >= 600,
                '600 từ là gợi ý nội bộ. Ưu tiên giải đáp đủ câu hỏi; Google không có số từ tối thiểu.'),
            $this->check('excerpt', 'Tóm tắt bài viết', 5, trim($data['excerpt'] ?? '') !== '',
                'Viết tóm tắt riêng để người đọc hiểu bài trước khi mở trang chi tiết.'),
        ];
        $score = array_sum(array_column($checks, 'points'));

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Sẵn sàng rà soát' : ($score >= 50 ? 'Cần hoàn thiện' : 'Cần bổ sung nội dung'),
            'wordCount' => $wordCount,
            'readingMinutes' => max(1, (int) ceil($wordCount / 200)),
            'checks' => $checks,
        ];
    }

    /** @return array{key: string, label: string, passed: bool, points: int, max: int, advice: string} */
    private function check(string $key, string $label, int $max, bool $passed, string $advice): array
    {
        return compact('key', 'label', 'passed', 'max', 'advice') + ['points' => $passed ? $max : 0];
    }

    private function plainText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function normalise(string $text): string
    {
        return Str::lower($this->plainText($text));
    }

    private function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
