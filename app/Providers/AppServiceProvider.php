<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        View::composer(['layouts.owner', 'owner.*'], function ($view) {
            $user = Auth::guard('owner')->user() ?? Auth::user();
            $isLodge = $user && method_exists($user, 'isOwner') && $user->isOwner() && $user->isLodgeOwner();
            $occupantTerm = $isLodge ? 'Renter' : 'Student';

            $view->with('occupantTerm', $occupantTerm);
            $view->with('occupantTermPlural', $occupantTerm . 's');
            $view->with('isLodgeOwner', $isLodge);
        });
    }
}
