<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FeatureFlags extends Page
{
    // Placeholder page kept to avoid broken route registrations after removal of UI.
    // Access to this page should not be possible from the UI after removal of the action.
    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.pages.feature-flags';

    protected static ?string $title = null;

    protected static ?string $slug = null;

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        abort(404);
    }
}
