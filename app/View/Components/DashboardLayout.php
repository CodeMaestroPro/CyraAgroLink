<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Single authenticated enterprise shell: sidebar + top navigation + content.
 */
class DashboardLayout extends Component
{
    /**
     * @param  list<array{label: string, href?: string|null}>  $breadcrumbs
     */
    public function __construct(
        public int $notificationsCount = 0,
        public string $title = 'Dashboard',
        public array $breadcrumbs = [],
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.dashboard');
    }
}
