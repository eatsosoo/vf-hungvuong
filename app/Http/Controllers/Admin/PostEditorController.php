<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Posts\AssessPostSeo;
use App\Actions\Posts\RenderPostContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostEditorRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

class PostEditorController extends Controller
{
    public function __invoke(
        PostEditorRequest $request,
        RenderPostContent $renderer,
        AssessPostSeo $assessor,
    ): JsonResponse {
        $data = $request->validated();
        $content = $renderer->handle($data['body'] ?? '');
        $cover = ! empty($data['media_id']) ? Media::query()->find($data['media_id']) : null;

        return response()->json([
            'html' => $content['html'],
            'headings' => $content['headings'],
            'assessment' => $assessor->handle($data, $content, $cover),
        ]);
    }
}
