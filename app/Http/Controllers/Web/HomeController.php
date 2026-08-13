<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Home\HomePageService;
use Illuminate\View\View;

/**
 * Public marketing home page.
 */
class HomeController extends Controller
{
    public function __construct(
        protected HomePageService $homePageService
    ) {
    }

    /**
     * Display the landing page with live platform data.
     */
    public function __invoke(): View
    {
        return view('pages.home', $this->homePageService->getHomePageData());
    }
}
