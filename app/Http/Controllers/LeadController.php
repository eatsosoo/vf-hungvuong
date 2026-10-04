<?php

namespace App\Http\Controllers;

use App\Actions\Leads\ReceiveLead;
use App\Http\Requests\StoreLeadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, ReceiveLead $action): RedirectResponse|JsonResponse
    {
        $action->handle($request->validated());

        $message = __('Đã gửi yêu cầu. Đại lý sẽ liên hệ với bạn.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return redirect()->route(app()->getLocale() === 'en' ? 'en.contact' : 'contact')->with('success', $message);
    }
}
