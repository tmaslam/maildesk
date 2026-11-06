<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sidebar label list (with unread counts) on every signed-in page.
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $view->with('sidebarLabels', \App\Models\Label::sidebar());
                // Brands that can send (Compose "From" list).
                $view->with('composeMailboxes', \App\Models\Mailbox::where('active', true)
                    ->whereNotNull('refresh_token')->whereNotNull('email')
                    ->orderBy('sort_order')->orderBy('brand_name')->get(['id', 'brand_name', 'email']));
            }
        });
    }
}
