<?php

namespace App\Http\Controllers;

use App\Actions\Posts\BuildPostSeo;
use App\Actions\Posts\RenderPostContent;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiteController extends Controller
{
    public function home(): View
    {
        $settings = Setting::values();

        return view('welcome', ['vehicles' => Vehicle::query()->active()->with(['media', 'variants'])->limit(6)->get(),
            'posts' => Post::query()->published()->with(['media', 'category'])->latest('published_at')->limit(3)->get(),
            'promotions' => Promotion::query()->active()->with('media')->latest()->limit(3)->get(),
            'banner' => ! empty($settings['banner_media_id']) ? Media::query()
                ->find($settings['banner_media_id']) : null]);
    }

    public function vehicles(Request $request): View
    {
        $data = $request->validate(['segment' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100']]);

        return view('site.vehicles', ['vehicles' => Vehicle::query()->active()->with(['media', 'variants'])
            ->when($data['segment'] ?? null, fn ($query, $value) => $query->where('segment', $value))
            ->when($data['q'] ?? null, fn ($query, $value) => $query->where('name', 'like', '%'.$value.'%'))
            ->orderBy('name')->paginate(12)->withQueryString(),
            'segments' => Vehicle::query()->active()->whereNotNull('segment')->distinct()->pluck('segment')]);
    }

    public function vehicle(Request $request, string $slug): View
    {
        $data = $request->validate(['color' => ['nullable', 'integer', 'min:1']]);
        $vehicle = Vehicle::query()->active()->where('slug',
            $slug)->with(['media',
                'variants',
                'colors.media'])->firstOrFail();
        $availableColors = $vehicle->colors->filter(fn ($color): bool => $color->media !== null)->values();
        $selectedColor = $availableColors->firstWhere('id', (int) ($data['color'] ?? 0))
            ?? $availableColors->firstWhere('media_id', $vehicle->media_id)
            ?? $availableColors->first();
        $canonical = route((app()->getLocale() === 'en' ? 'en.' : '').'vehicles.show', $vehicle->slug);
        $siteName = Setting::values()['site_name'] ?? 'VinFast Hùng Vương';
        $description = Str::limit($vehicle->description ?: 'Tìm hiểu '.$vehicle->name.' tại '.$siteName
            .'. Liên hệ để được tư vấn, nhận báo giá hoặc đăng ký lái thử.', 160);
        $breadcrumbs = [
            ['label' => __('Trang chủ'), 'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'home')],
            ['label' => __('Dòng xe'), 'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'vehicles.index')],
            ['label' => $vehicle->name],
        ];
        $vehicleSchema = [
            '@type' => 'Vehicle',
            '@id' => $canonical.'#vehicle',
            'name' => $vehicle->name,
            'url' => $canonical,
        ];
        if ($vehicle->description) {
            $vehicleSchema['description'] = $vehicle->description;
        }
        if ($vehicle->media) {
            $vehicleSchema['image'] = $vehicle->media->url();
        }

        return view('site.vehicle', ['vehicle' => $vehicle,
            'availableColors' => $availableColors,
            'selectedColor' => $selectedColor,
            'posts' => $vehicle->posts()->published()->with(['media', 'category'])
                ->latest('published_at')->limit(6)->get(),
            'promotions' => $vehicle->promotions()->active()->get(),
            'breadcrumbs' => $breadcrumbs,
            'seo' => [
                'title' => $vehicle->name.' · '.$siteName,
                'description' => $description,
                'canonical' => $canonical,
                'robots' => 'index,follow',
                'ogType' => 'website',
                'image' => $vehicle->media?->url(),
                'imageAlt' => $vehicle->media ? ($vehicle->media->alt ?: $vehicle->name) : null,
                'structuredData' => [$vehicleSchema, $this->breadcrumbSchema($breadcrumbs)],
            ],
        ]);
    }

    public function posts(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:180'], 'tag' => ['nullable', 'string', 'max:180'],
            'page' => ['nullable', 'integer', 'min:1']]);
        $query = Post::query()->published()->with(['media', 'category']);
        if (! empty($data['q'])) {
            $query->where('title', 'like', '%'.$data['q'].'%');
        }
        foreach (['category' => 'category', 'tag' => 'tags'] as $key => $relation) {
            if (! empty($data[$key])) {
                $query->whereHas($relation, fn ($q) => $q->where('slug', $data[$key]));
            }
        }

        $posts = $query->latest('published_at')->paginate(12)->withQueryString();
        $categories = Category::query()->whereHas('posts', fn ($query) => $query->published())
            ->withCount(['posts' => fn ($query) => $query->published()])->orderBy('name')->get();
        $tags = Tag::query()->whereHas('posts', fn ($query) => $query->published())
            ->withCount(['posts' => fn ($query) => $query->published()])->orderBy('name')->get();
        $activeCategory = $categories->firstWhere('slug', $data['category'] ?? null);
        $activeTag = $tags->firstWhere('slug', $data['tag'] ?? null);
        $searchQuery = $data['q'] ?? '';
        $canonicalFilters = array_filter([
            'category' => $data['category'] ?? null,
            'tag' => $data['tag'] ?? null,
            'q' => $searchQuery,
            'page' => $posts->currentPage() > 1 ? $posts->currentPage() : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
        $canonical = route((app()->getLocale() === 'en' ? 'en.' : '').'posts.index', $canonicalFilters);
        $context = collect([$activeCategory?->name, $activeTag?->name])->filter()->implode(' · ');
        $title = ($context ? $context.' · ' : '').__('Góc tư vấn xe điện');
        $title .= ' · '.(Setting::values()['site_name'] ?? 'VinFast Hùng Vương');
        $description = __('Khám phá bài viết về xe điện, kinh nghiệm sử dụng '
            .'và tư vấn chọn xe tại VinFast Hùng Vương.');
        $breadcrumbs = [
            ['label' => __('Trang chủ'), 'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'home')],
            ['label' => __('Góc tư vấn')],
        ];
        if ($activeCategory || $activeTag) {
            $breadcrumbs[1]['url'] = route((app()->getLocale() === 'en' ? 'en.' : '').'posts.index');
            $breadcrumbs[] = ['label' => $context];
        }
        $collectionSchema = [
            '@type' => 'CollectionPage',
            '@id' => $canonical,
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'inLanguage' => app()->getLocale() === 'en' ? 'en-US' : 'vi-VN',
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $posts->count(),
                'itemListElement' => $posts->getCollection()->values()->map(
                    fn (Post $post, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => ($posts->firstItem() ?? 1) + $index,
                        'name' => $post->title,
                        'url' => route((app()->getLocale() === 'en' ? 'en.' : '').'posts.show', $post->slug),
                    ],
                )->all(),
            ],
        ];

        return view('site.posts', compact('posts', 'categories', 'tags', 'activeCategory',
            'activeTag', 'searchQuery', 'breadcrumbs') + [
                'seo' => [
                    'title' => $title,
                    'description' => $description,
                    'canonical' => $canonical,
                    'robots' => $searchQuery !== '' || $posts->isEmpty() ? 'noindex,follow' : 'index,follow',
                    'ogType' => 'website',
                    'image' => null,
                    'imageAlt' => null,
                    'structuredData' => [$collectionSchema, $this->breadcrumbSchema($breadcrumbs)],
                ],
            ]);
    }

    public function post(string $slug, RenderPostContent $renderer, BuildPostSeo $seo): View|RedirectResponse
    {
        $post = Post::query()->published()->where('slug',
            $slug)->with(['author',
                'media',
                'shareMedia',
                'category',
                'tags'])->first();
        if (! $post) {
            $redirect = PostRedirect::query()->where('slug', $slug)
                ->whereHas('post', fn ($query) => $query->published())->with('post')->firstOrFail();

            return redirect()->route((app()->getLocale() === 'en' ? 'en.' : '').'posts.show', $redirect->post->slug, 301);
        }
        $content = $renderer->handle($post->body);

        return view('site.post', ['post' => $post, 'content' => $content, 'preview' => false,
            'related' => Post::query()->published()->where('id', '!=', $post->id)
                ->where('category_id', $post->category_id)->with(['media', 'category'])
                ->latest('published_at')->limit(4)->get(),
            'vehicles' => $post->vehicles()->active()->with(['media', 'variants'])->get()]
            + $seo->handle($post, $content));
    }

    public function promotions(Request $request): View
    {
        $data = $request->validate([
            'vehicle' => ['nullable', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $promotionVehicles = Vehicle::query()->active()
            ->whereHas('promotions', fn ($query) => $query->active())
            ->withCount(['promotions' => fn ($query) => $query->active()])->orderBy('name')->get();
        $activeVehicle = $promotionVehicles->firstWhere('slug', $data['vehicle'] ?? null);
        $promotions = Promotion::query()->active()
            ->with(['media', 'vehicles' => fn ($query) => $query->active()])
            ->when($data['vehicle'] ?? null,
                fn ($query, $slug) => $query->whereHas('vehicles',
                    fn ($query) => $query->active()->where('slug', $slug)))
            ->latest()->orderByDesc('id')->paginate(12)->withQueryString();
        $promotionSummaries = $promotions->getCollection()->mapWithKeys(
            fn (Promotion $promotion): array => [$promotion->id => $this->promotionSummary($promotion)]);

        return view('site.promotions', compact(
            'promotions', 'promotionVehicles', 'activeVehicle', 'promotionSummaries',
        ));
    }

    public function promotion(string $slug, RenderPostContent $renderer): View
    {
        $promotion = Promotion::query()->active()->where('slug', $slug)->with('media')->firstOrFail();

        return view('site.promotion', ['promotion' => $promotion, 'content' => $renderer->handle($promotion->body),
            'promotionSummary' => $this->promotionSummary($promotion),
            'vehicles' => $promotion->vehicles()->active()->with(['media', 'variants'])->get()]);
    }

    /**
     * @return array{benefits: list<string>, summary: string, is_demo: bool}
     */
    private function promotionSummary(Promotion $promotion): array
    {
        preg_match_all('/^\h*[-*]\h+(.+)$/mu', $promotion->body, $matches);
        $paragraphs = preg_split('/\R\s*\R/u', $promotion->body) ?: [];
        $summary = collect($paragraphs)->first(
            fn (string $paragraph): bool => preg_match('/\A\s*[#>\-*]/u', $paragraph) !== 1);

        return [
            'benefits' => array_map(fn (string $text): string => trim(strip_tags($text)),
                array_slice($matches[1], 0, 3)),
            'summary' => Str::limit(trim(strip_tags($summary ?? '')), 180),
            'is_demo' => str_contains($promotion->body, 'Nội dung ưu đãi mẫu'),
        ];
    }

    public function page(string $slug, RenderPostContent $renderer): View
    {
        $page = Page::query()->active()->where('slug', $slug)->firstOrFail();

        return view('site.page', ['page' => $page, 'content' => $renderer->handle($page->body)]);
    }

    public function contact(): View
    {
        return view('site.contact',
            ['vehicles' => Vehicle::query()->active()->with('variants')->orderBy('name')->get()]);
    }

    public function media(Media $media): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($media->path), 404);

        return response()->file(Storage::disk('local')->path($media->path), [
            'Content-Type' => 'image/webp', 'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [route('sitemap.page', ['section' => 'static', 'page' => 1])];
        foreach (['vehicles' => Vehicle::query()->active(), 'posts' => Post::query()->published(),
            'promotions' => Promotion::query()->active(), 'pages' => Page::query()->active()] as $section => $query) {
            for ($page = 1; $page <= (int) ceil($query->count() / 1000); $page++) {
                $urls[] = route('sitemap.page', compact('section', 'page'));
            }
        }

        return response()->view('site.sitemap-index', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function sitemapPage(string $section, int $page): Response
    {
        abort_if($page < 1, 404);
        if ($section === 'static') {
            abort_unless($page === 1, 404);
            $urls = [route((app()->getLocale() === 'en' ? 'en.' : '').'home'),
                route((app()->getLocale() === 'en' ? 'en.' : '').'vehicles.index'),
                route((app()->getLocale() === 'en' ? 'en.' : '').'posts.index'),
                route((app()->getLocale() === 'en' ? 'en.' : '').'promotions.index'),
                route((app()->getLocale() === 'en' ? 'en.' : '').'contact')];
        } else {
            $query = match ($section) {
                'vehicles' => Vehicle::query()->active(), 'posts' => Post::query()->published(),
                'promotions' => Promotion::query()->active(), 'pages' => Page::query()->active(),
                default => abort(404),
            };
            abort_if($page > (int) ceil($query->count() / 1000), 404);
            $route = match ($section) {
                'vehicles' => 'vehicles.show', 'posts' => 'posts.show',
                'promotions' => 'promotions.show', 'pages' => 'pages.show',
            };
            $urls = $query->select('slug')->orderBy('id')->forPage($page, 1000)->get()
                ->map(fn ($record): string => route($route, $record->slug))->all();
        }

        return response()->view('site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    /**
     * @param  array<int, array{label: string, url?: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(array $breadcrumbs): array
    {
        return [
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
}
