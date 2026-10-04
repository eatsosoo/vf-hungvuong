<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Posts\AssessPostSeo;
use App\Actions\Posts\BuildPostSeo;
use App\Actions\Posts\BulkUpdatePosts;
use App\Actions\Posts\RenderPostContent;
use App\Actions\Posts\SavePost;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Post::class);
        $query = Post::query()->with(['author', 'category']);
        if ($request->user()->role === UserRole::Editor) {
            $query->where('user_id', $request->user()->id);
        }
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,scheduled,published'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'seo' => ['nullable', 'in:complete,incomplete'],
            'published_from' => ['nullable', 'date_format:Y-m-d'],
            'published_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:published_from'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in(['title', 'status', 'author', 'seo', 'id', 'updated_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        foreach (['status', 'category_id', 'user_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (filled($filters['q'] ?? null)) {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }
        foreach (['published_from' => '>=', 'published_to' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->whereDate('published_at', $operator, $filters[$key]);
            }
        }
        if (($filters['seo'] ?? null) === 'complete') {
            $query->whereNotNull('seo_title')->where('seo_title', '!=', '')
                ->whereNotNull('seo_description')->where('seo_description', '!=', '')->whereNotNull('media_id');
        } elseif (($filters['seo'] ?? null) === 'incomplete') {
            $query->where(function (Builder $seoQuery): void {
                $seoQuery->whereNull('seo_title')->orWhere('seo_title', '')
                    ->orWhereNull('seo_description')->orWhere('seo_description', '')->orWhereNull('media_id');
            });
        }

        $sortColumn = match ($filters['sort'] ?? null) {
            'author' => User::query()->select('name')->whereColumn('users.id', 'posts.user_id'),
            'seo' => DB::raw("CASE WHEN COALESCE(seo_title, '') != '' AND COALESCE(seo_description, '') != '' "
                .'AND media_id IS NOT NULL THEN 1 ELSE 0 END'),
            default => $filters['sort'] ?? 'created_at',
        };
        $query->orderBy($sortColumn, $filters['direction'] ?? (isset($filters['sort']) ? 'asc' : 'desc'))
            ->orderByDesc('id');

        return view('admin.posts.index', ['posts' => $query
            ->paginate((int) ($filters['per_page'] ?? 20))->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'authors' => User::query()
                ->when($request->user()->role === UserRole::Editor,
                    fn (Builder $authorQuery): Builder => $authorQuery->whereKey($request->user()->id))
                ->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Post::class);

        return $this->form(new Post);
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        return $this->form($post);
    }

    private function form(Post $post): View
    {
        $post->load(['tags', 'vehicles', 'media']);
        $data = array_replace($post->getAttributes(), session()->getOldInput());
        foreach (['title', 'slug', 'excerpt', 'body', 'focus_keyword', 'seo_title', 'seo_description'] as $key) {
            $data[$key] = is_string($data[$key] ?? null) ? $data[$key] : '';
        }
        $content = app(RenderPostContent::class)->handle($data['body'] ?? '');
        $cover = filter_var($data['media_id'] ?? null, FILTER_VALIDATE_INT)
            ? Media::query()->find($data['media_id']) : null;

        return view('admin.posts.form', ['post' => $post,
            'assessment' => app(AssessPostSeo::class)->handle($data, $content, $cover),
            'categories' => Category::query()->orderBy('name')->get(), 'tags' => Tag::query()->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderBy('name')->get(),
            'media' => Media::query()->latest()->limit(100)->get()]);
    }

    public function store(PostRequest $request, SavePost $action): RedirectResponse
    {
        $post = $action->handle($request->user(), $request->validated());

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã lưu bài viết.');
    }

    public function update(PostRequest $request, Post $post, SavePost $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated(), $post);

        return back()->with('success', 'Đã lưu bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã xóa bài viết.');
    }

    public function preview(Post $post, RenderPostContent $renderer, BuildPostSeo $seo): View
    {
        Gate::authorize('preview', $post);
        $post->load(['author', 'category', 'media', 'shareMedia', 'tags']);
        $content = $renderer->handle($post->body);

        return view('site.post', ['post' => $post,
            'content' => $content,
            'related' => collect(),
            'vehicles' => collect(),
            'preview' => true] + $seo->handle($post, $content, preview: true));
    }

    public function bulk(Request $request, BulkUpdatePosts $action): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:posts,id'], 'operation' => ['required', 'in:delete,draft']]);
        $action->handle($request->user(), $data);

        return back()->with('success', 'Đã xử lý các bài được chọn.');
    }
}
