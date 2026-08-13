<?php

namespace App\Providers;

use App\Contracts\FirebaseIdTokenVerifierInterface;
use App\Services\Firebase\KreaitFirebaseIdTokenVerifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FirebaseIdTokenVerifierInterface::class, KreaitFirebaseIdTokenVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
