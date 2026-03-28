<?php
// app/Http/Controllers/V1/NotificationController.php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use App\Models\V1\Notification;
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
        $user = auth()->user();

        // Determine query scope: admin sees all, others see only their own
        if (in_array($user->role, ['super_admin', 'admin'])) {
            $query = Notification::query();
        } else {
            $query = Notification::where('user_id', $user->id);
        }

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

        $notifications = $query->with(['user', 'template'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

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
        // Check permission: user can view only his own notifications unless admin
        $user = auth()->user();
        if (!in_array($user->role, ['super_admin', 'admin']) && $notification->user_id != $user->id) {
            return response()->json(['message' => 'غير مصرح لك بعرض هذا الإشعار'], 403);
        }

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
        $user = auth()->user();
        if (!in_array($user->role, ['super_admin', 'admin']) && $notification->user_id != $user->id) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

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
    //     $user = auth()->user();
    //     if (!in_array($user->role, ['super_admin', 'admin']) && $notification->user_id != $user->id) {
    //         return response()->json(['message' => 'غير مصرح لك'], 403);
    //     }

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
        $user = auth()->user();
        $count = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
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
}