<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileAppPreviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mobile app screens preview for stakeholders and demos.
 */
class MobileAppPreviewController extends Controller
{
    public function __construct(
        protected MobileAppPreviewService $mobileAppPreviewService
    ) {
    }

    /**
     * Display the mobile app screens preview.
     */
    public function __invoke(Request $request): View
    {
        $data = $this->mobileAppPreviewService->getPreviewData($request->user());

        return view('mobile.preview', [
            'greetingName' => $data['greeting_name'],
            'dashboard' => $data['dashboard'],
            'marketplace' => $data['marketplace'],
            'investments' => $data['investments'],
            'aiSuggestions' => $data['ai_suggestions'],
            'wallet' => $data['wallet'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }
}
