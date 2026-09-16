<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Every current use of <x-app-layout> is an authenticated, private page
 * (dashboard, profile, admin, enterprise contact) — noindex defaults to
 * true so none of them can ever be indexed by accident; a genuinely
 * public page rendered through this layout (e.g. StartCheck's Livewire
 * #[Layout('layouts.app', [...])] usage) explicitly overrides it.
 */
class AppLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public bool $noindex = true,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
