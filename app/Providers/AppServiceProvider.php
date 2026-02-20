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
        try {
            config()->set('openai.api_key', setting('openai_api_key', env('OPENAI_API_KEY'), 'ai'));
            config()->set('openai.organization', setting('openai_organization', env('OPENAI_ORGANIZATION'), 'ai'));
            config()->set('openai.request_timeout', setting('openai_request_timeout', env('OPENAI_REQUEST_TIMEOUT', 30), 'ai'));
        } catch (\Exception $e) {
        }
    }
}
