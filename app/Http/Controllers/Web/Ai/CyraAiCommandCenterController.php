<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\CyraAiCommandCenterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CyraAI Command Center — AI assistant home with prompts and insights.
 */
class CyraAiCommandCenterController extends Controller
{
    public function __construct(
        protected CyraAiCommandCenterService $cyraAiCommandCenterService
    ) {
    }

    /**
     * Display the CyraAI Command Center.
     */
    public function __invoke(Request $request): View
    {
        $data = $this->cyraAiCommandCenterService->getCommandCenterData($request->user());

        return view('ai.command-center', [
            'greetingName' => $data['greeting_name'],
            'prompts' => $data['prompts'],
            'insights' => $data['insights'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }
}
