<?php

namespace App\Providers;

use App\Services\ActiveExerciceService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Schema;
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
        Vite::prefetch(concurrency: 3);

        View::composer('layouts.app', function ($view) {
            $activeExercice = app(ActiveExerciceService::class);
            $availableExercices = Schema::hasTable('societes') && Schema::hasTable('balance_items')
                ? $activeExercice->available()
                : [];

            $view->with([
                'activeExercice' => $activeExercice->current(),
                'availableExercices' => $availableExercices,
            ]);
        });
    }
}
