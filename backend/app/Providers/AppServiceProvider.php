<?php

namespace App\Providers;

use App\Services\Ai\LlmClient;
use App\Services\Refunds\PolicyEngine;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LlmClient::class, fn () => LlmClient::fromConfig());
        $this->app->singleton(PolicyEngine::class, fn () => PolicyEngine::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('refund-submissions', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('conversations', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('conversation-messages', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip().'|'.$request->input('email')));
    }
}
