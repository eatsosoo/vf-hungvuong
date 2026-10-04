<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreImage;
use App\Actions\Media\StoreImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('manage-content');
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest,name_asc,name_desc'],
            'columns' => ['nullable', 'in:2,3,4,6'],
            'id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Media::query()->when($data['id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($data['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('original_name', 'like', '%'.$search.'%')->orWhere('alt', 'like', '%'.$search.'%');
            }));
        [$column, $direction] = match ($data['sort'] ?? 'newest') {
            'oldest' => ['created_at', 'asc'],
            'name_asc' => ['original_name', 'asc'],
            'name_desc' => ['original_name', 'desc'],
            default => ['created_at', 'desc'],
        };
        $media = $query->orderBy($column, $direction)->orderBy('id', $direction)
            ->paginate(24)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (Media $item): array => $this->mediaData($item)),
                'meta' => ['total' => $media->total(), 'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage()],
            ]);
        }

        $postLimit = (int) ini_parse_quantity(ini_get('post_max_size'));

        return view('admin.media.index', ['media' => $media,
            'maxUploadBytes' => min(5 * 1024 * 1024, (int) UploadedFile::getMaxFilesize()),
            'maxBatchBytes' => $postLimit > 0 ? max(1, $postLimit - 65536) : 100 * 1024 * 1024]);
    }

    public function store(MediaRequest $request, StoreImage $action, StoreImages $batch): RedirectResponse|JsonResponse
    {
        if ($request->hasFile('images')) {
            $media = $batch->handle($request->file('images'), $request->user(), $request->validated('alts') ?? []);
            if ($request->expectsJson()) {
                return response()->json(['data' => array_map($this->mediaData(...), $media)], 201);
            }

            return back()->with('success', 'Đã tải và tối ưu '.count($media).' ảnh.');
        }
        $file = $request->file('image');
        $alt = $request->validated('alt') ?? mb_substr(basename($file->getClientOriginalName()), 0, 255);
        $media = $action->handle($file, $request->user(), $alt);
        if ($request->expectsJson()) {
            return response()->json($this->mediaData($media), 201);
        }

        return back()->with('success', 'Đã tải và tối ưu ảnh.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        Gate::authorize('manage-content');
        $media->update($request->validate(['alt' => ['nullable', 'string', 'max:255']]));

        return back()->with('success', 'Đã cập nhật mô tả ảnh.');
    }

    /** @return array{id: int, url: string, alt: ?string, original_name: string, width: int, height: int, size: int} */
    private function mediaData(Media $media): array
    {
        return ['id' => $media->id, 'url' => $media->url(), 'alt' => $media->alt,
            'original_name' => $media->original_name, 'width' => $media->width,
            'height' => $media->height, 'size' => $media->size];
    }
}
