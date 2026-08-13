<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Split-panel authentication layout matching CyraAgroLink UI.
 */
class AuthLayout extends Component
{
    public function __construct(
        public string $title = 'Login',
        public string $heading = 'Welcome Back!',
        public ?string $subheading = null,
    ) {
        $this->subheading ??= 'Login to continue to your '.config('cyra.brand').' account';
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.auth');
    }
}
