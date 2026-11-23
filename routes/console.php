<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// إشعارات الاشتراكات والزيارات — عبر HTTP + CRON_TOKEN
// ============================================================

/**
 * استدعاء endpoint الإشعارات مع التوكن.
 */
$callNotificationEndpoint = function (string $endpoint): void {
    $token   = env('CRON_TOKEN', 'd0776b6e03e91f38d9cff336a3e09c922e8bff177fa9172693806851812738d5');
    $baseUrl = rtrim(config('app.url'), '/');

    try {
        $response = Http::withHeaders([
                'X-Cron-Token' => $token,
                'Accept'       => 'application/json',
            ])
            ->timeout(60)
            ->get("{$baseUrl}/api/v1/notifications/{$endpoint}");

        if ($response->failed()) {
            Log::error("Cron endpoint failed: {$endpoint}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } 
        // else {
        //     Log::info("Cron endpoint ok: {$endpoint}", $response->json() ?? []);
        // }
    } catch (\Throwable $e) {
        Log::error("Cron endpoint exception: {$endpoint}", ['error' => $e->getMessage()]);
    }
};

// 1) الاشتراكات المنتهية (تجديد آلي أو تحويل إلى expired) — يومياً 00:30
Schedule::call(fn () => $callNotificationEndpoint('subscription-expired'))
    ->dailyAt('00:30')
    ->name('notifications:subscription-expired')
    ->withoutOverlapping();

// 2) تذكير قبل انتهاء الاشتراك بـ 5 أيام — يومياً 08:00
Schedule::call(fn () => $callNotificationEndpoint('subscription-expiry-reminder'))
    ->dailyAt('08:00')
    ->name('notifications:subscription-expiry-reminder')
    ->withoutOverlapping();

// 3) تذكير المفتشين بزيارات الغد — يومياً 18:00
Schedule::call(fn () => $callNotificationEndpoint('reminder-visit-tomorrow'))
    ->dailyAt('18:00')
    ->name('notifications:visit-tomorrow')
    ->withoutOverlapping();