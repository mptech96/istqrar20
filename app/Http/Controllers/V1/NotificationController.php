<?php
// app/Http/Controllers/V1/NotificationController.php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use App\Models\V1\Notification;
use App\Models\Visit;
use App\Models\Company;
use App\Models\V1\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/notifications',
        operationId: 'getNotifications',
        summary: 'عرض قائمة الإشعارات',
        description: 'استرجاع إشعارات المستخدم الحالي (أو جميع الإشعارات للمشرف)',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'read', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'تصفية مقروءة/غير مقروءة'),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string'), description: 'نوع الإشعار (مرتبط بالقالب)'),
            new OA\Parameter(name: 'from_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'قائمة الإشعارات'),
            new OA\Response(response: 401, description: 'غير مصرح به'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Notification::query();

        // Filter by read/unread
        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        // Filter by notification type (via template)
        if ($request->has('type')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $notifications = $query->with(['user', 'template', 'entity'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', setting('items_per_page', '15', 'system')));

        return NotificationResource::collection($notifications);
    }

    // #[OA\Post(
    //     path: '/api/v1/notifications',
    //     operationId: 'createNotification',
    //     summary: 'إنشاء إشعار جديد',
    //     description: 'إنشاء إشعار يدوياً (للمشرفين فقط)',
    //     tags: ['Notifications'],
    //     security: [['bearerAuth' => []]],
    //     requestBody: new OA\RequestBody(
    //         required: true,
    //         content: new OA\MediaType(
    //             mediaType: 'application/json',
    //             schema: new OA\Schema(
    //                 required: ['user_id', 'title', 'body'],
    //                 properties: [
    //                     new OA\Property(property: 'user_id', type: 'integer'),
    //                     new OA\Property(property: 'notification_template_id', type: 'integer', nullable: true),
    //                     new OA\Property(property: 'title', type: 'string'),
    //                     new OA\Property(property: 'body', type: 'string'),
    //                     new OA\Property(property: 'scheduled_date', type: 'string', format: 'date-time', nullable: true),
    //                     new OA\Property(property: 'url', type: 'string', nullable: true),
    //                 ]
    //             )
    //         )
    //     ),
    //     responses: [
    //         new OA\Response(response: 201, description: 'تم إنشاء الإشعار بنجاح'),
    //         new OA\Response(response: 403, description: 'غير مصرح به'),
    //         new OA\Response(response: 422, description: 'بيانات غير صالحة'),
    //     ]
    // )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'notification_template_id' => ['required', 'integer', 'exists:notification_templates,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'array'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'entity_id' => ['required', 'integer'],
            'entity_type' => ['required', 'string', 'max:200'],
            'url' => ['nullable', 'string', 'max:1000'],
        ]);

        $template = NotificationTemplate::active()->find($data['notification_template_id']);

        $data['title'] = $this->applyTemplate($template->title_template, $data['title'] ?? null);
        $data['body'] = $this->applyTemplate($template->body_template, $data['body'] ?? null);

        $notification = Notification::create($data);

        return new NotificationResource($notification);
    }

    #[OA\Get(
        path: '/api/v1/notifications/{notification}',
        operationId: 'getNotification',
        summary: 'عرض تفاصيل إشعار',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'تفاصيل الإشعار'),
            new OA\Response(response: 404, description: 'الإشعار غير موجود'),
        ]
    )]
    public function show(Notification $notification)
    {
        return new NotificationResource($notification->load(['user', 'template']));
    }

    #[OA\Patch(
        path: '/api/v1/notifications/{notification}/read',
        operationId: 'markNotificationRead',
        summary: 'تحديد إشعار كمقروء',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'تم تحديث الإشعار كمقروء'),
            new OA\Response(response: 404, description: 'الإشعار غير موجود'),
        ]
    )]
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json(['message' => 'تم تحديث الإشعار كمقروء']);
    }

    // #[OA\Delete(
    //     path: '/api/v1/notifications/{notification}',
    //     operationId: 'deleteNotification',
    //     summary: 'حذف إشعار',
    //     tags: ['Notifications'],
    //     security: [['bearerAuth' => []]],
    //     parameters: [
    //         new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    //     ],
    //     responses: [
    //         new OA\Response(response: 200, description: 'تم حذف الإشعار بنجاح'),
    //         new OA\Response(response: 404, description: 'الإشعار غير موجود'),
    //     ]
    // )]
    // public function destroy(Notification $notification)
    // {
    //     $notification->delete();

    //     return response()->json(['message' => 'تم حذف الإشعار بنجاح']);
    // }

    /**
     * Bulk mark as read for current user
     */
    #[OA\Post(
        path: '/api/v1/notifications/mark-all-read',
        operationId: 'markAllNotificationsRead',
        summary: 'تحديد جميع إشعارات المستخدم كمقروءة',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'تم تحديث جميع الإشعارات كمقروءة'),
        ]
    )]
    public function markAllAsRead()
    {
        $count = Notification::whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => "تم تحديث {$count} إشعار كمقروء"]);
    }
    
    private static function applyTemplate(string $template, array $data = null): string
    {
        if(is_array($data)) {
            
            foreach ($data as $key => $value) {
                $template = str_replace('{' . $key . '}', $value, $template);
            }
        }
        
        return $template;
    }

    /**
     * حماية مهام Cron: إذا ضُبط CRON_TOKEN في .env يجب تمريره كـ ?token= أو ترويسة X-Cron-Token
     */
    private function authorizeCron()
    {
        $expected = env('CRON_TOKEN', 'd0776b6e03e91f38d9cff336a3e09c922e8bff177fa9172693806851812738d5');
        if ($expected && !hash_equals($expected, (string) (request()->query('token') ?? request()->header('X-Cron-Token')))) {
            abort(response()->json(['status' => false, 'message' => 'Unauthorized'], 401));
        }
    }

    /**
     * يومياً: الاشتراكات المنتهية (اليوم أو قبله ولم تُعالج)
     * - إذا كان التجديد الآلي مفعّلاً: يُمدد الاشتراك بنفس مدته السابقة ويُرسل إشعار تجديد
     * - غير ذلك: تتحول الحالة إلى expired ويُشعر مدير المنشأة ومدراء النظام
     */
    public function subscription_expired()
    {
        $this->authorizeCron();
        $service = app(\App\Services\V1\NotificationService::class);
        $renewed = 0; $expired = 0;
        $companies = Company::activeSubscription()
            ->whereNotNull('subscription_end')
            ->whereDate('subscription_end', '<=', now()->toDateString())
            ->get();
        foreach ($companies as $company) {
            if ($company->auto_renew) {
                // المدة الأصلية للاشتراك
                $originalStart = $company->subscription_start ?? $company->subscription_end->copy()->subYear();
                $durationDays  = max(1, $originalStart->diffInDays($company->subscription_end));
            
                // ابدأ من اليوم
                $newStart = now()->startOfDay();
                $newEnd   = $newStart->copy()->addDays($durationDays);
            
                $company->forceFill([
                    'subscription_start'  => $newStart->toDateString(),
                    'subscription_end'    => $newEnd->toDateString(),
                    'subscription_status' => 'active',
                ])->save();
            
                $service->subscription_renewed($company->fresh());
                $renewed++;
            } else {
                $company->forceFill(['subscription_status' => 'expired'])->save();
                $service->subscription_expired($company);
                $expired++;
            }
        }
        return response()->json(['status' => true, 'renewed' => $renewed, 'expired' => $expired]);
    }

    /**
     * يومياً: تذكير قبل انتهاء الاشتراك بـ 5 أيام
     */
    public function subscription_expiry_reminder()
    {
        $this->authorizeCron();
        $companies = Company::subscriptionEndsInDays(5)->get();
        foreach ($companies as $company) {
            app(\App\Services\V1\NotificationService::class)->subscription_expiry_reminder($company);
        }
        return response()->json(['status' => true, 'count' => $companies->count()]);
    }

    /**
     * يومياً: تذكير كل مفتش بعدد زياراته المجدولة غداً
     */
    public function reminder_visit_tomorrow()
    {
        $this->authorizeCron();
        $visits = Visit::tomorrowVisits()->get();
        foreach ($visits as $visit) {
            app(\App\Services\V1\NotificationService::class)->reminder_visit_tomorrow($visit);
        }
        return response()->json(['status' => true, 'count' => $visits->count()]);
    }
}