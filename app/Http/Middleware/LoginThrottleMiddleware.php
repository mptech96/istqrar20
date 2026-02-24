<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\V1\LoginAttempt;
use Carbon\Carbon;

class LoginThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if( setting('enable_superior_security', 'off', 'security') === 'off' ) return $next($request);
        
        $email = $request->email;
        $ip = $request->ip();

        // التحقق من الحظر قبل معالجة الطلب
        $blockStatus = $this->checkBlockStatus($email, $ip);
        
        if ($blockStatus['blocked']) {
            return response()->json([
                'success' => false,
                'message' => $blockStatus['message']
            ], 429);
        }

        // إضافة معلومات للمستخدم في الـ request
        $request->merge([
            'throttle_info' => [
                'attempts_remaining' => $this->getRemainingAttempts($email, $ip)
            ]
        ]);

        return $next($request);
    }
    
    private function checkBlockStatus(string $email, string $ip)
    {
        $attempts = LoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->orderBy('attempted_at', 'desc')
            ->get();

        $failedCount = $attempts->count();
        
        if ($failedCount == 0) {
            return ['blocked' => false];
        }

        $lastAttempt = $attempts->first();
        $now = Carbon::now();

        // المرحلة 1: 3 محاولات فاشلة → انتظار ساعة
        if ($failedCount >= 3 && $failedCount < 5) {
            $waitUntil = $lastAttempt->attempted_at->addHour();
            if ($now->lt($waitUntil)) {
                $minutesLeft = ceil($now->diffInMinutes($waitUntil));
                return [
                    'blocked' => true,
                    'message' => "تم تجاوز الحد المسموح. انتظر {$minutesLeft} دقيقة"
                ];
            }
        }

        // المرحلة 2: 5 محاولات فاشلة → انتظار يوم
        if ($failedCount >= 5 && $failedCount < 6) {
            $waitUntil = $lastAttempt->attempted_at->addDay();
            if ($now->lt($waitUntil)) {
                $hoursLeft = ceil($now->diffInHours($waitUntil));
                return [
                    'blocked' => true,
                    'message' => "تم تجاوز الحد المسموح. انتظر {$hoursLeft} ساعة"
                ];
            }
        }

        // المرحلة 3: 6 محاولات → حظر نهائي
        if ($failedCount >= 6) {
            return [
                'blocked' => true,
                'message' => "تم حظر الحساب نهائياً. تواصل مع الدعم"
            ];
        }

        return ['blocked' => false];
    }

    private function getRemainingAttempts(string $email, string $ip)
    {
        $failedCount = LoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->count();

        if ($failedCount < 3) {
            return 3 - $failedCount;
        } elseif ($failedCount < 5) {
            return 2 - ($failedCount - 3);
        } elseif ($failedCount < 6) {
            return 1 - ($failedCount - 5);
        }

        return 0;
    }
    
    public function handleLoginAttempt(string $email, string $ip, bool $successful)
    {
        if( setting('enable_superior_security', 'off', 'security') === 'off' ) return;
        
        if ($successful) {
            // تسجيل محاولة ناجحة وحذف الفاشلة
            LoginAttempt::where('email', $email)
                ->where('ip_address', $ip)
                ->where('successful', false)
                ->delete();
                
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'attempted_at' => now(),
                'successful' => true
            ]);
        } else {
            // تسجيل محاولة فاشلة
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'attempted_at' => now(),
                'successful' => false
            ]);
        }
    }
}
