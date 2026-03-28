<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreServiceRequestRequest;
use App\Http\Requests\V1\UpdateServiceRequestRequest;
use App\Http\Resources\V1\ServiceRequestResource;
use App\Models\V1\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ServiceRequestController extends Controller
{
    #[OA\Get(
        path: '/api/v1/service-requests',
        summary: 'عرض قائمة الطلبات',
        tags: ['Service Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'request_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['package_request', 'contact_message'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['new', 'in_review', 'closed'])),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'created_at', enum: ['customer_name', 'phone', 'status', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'قائمة الطلبات')
        ]
    )]
    public function index(Request $request)
    {
        $query = ServiceRequest::query();

        // Filtering
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->input('request_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $requests = $query->paginate($request->input('per_page', 15));

        return ServiceRequestResource::collection($requests);
    }

    #[OA\Post(
        path: '/api/v1/service-requests',
        summary: 'إنشاء طلب جديد',
        tags: ['Service Requests'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['request_type', 'customer_name', 'phone'],
                properties: [
                    new OA\Property(property: 'request_type', type: 'string', enum: ['package_request', 'contact_message']),
                    new OA\Property(property: 'package_name', type: 'string', nullable: true),
                    new OA\Property(property: 'customer_name', type: 'string'),
                    new OA\Property(property: 'company_name', type: 'string', nullable: true),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'subject', type: 'string', nullable: true),
                    new OA\Property(property: 'message', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'تم الإنشاء'),
            new OA\Response(response: 422, description: 'بيانات غير صالحة')
        ]
    )]
    public function store(StoreServiceRequestRequest $request)
    {
        $data = $request->validated();
        // تعيين الحالة الافتراضية إذا لم ترسل
        $data['status'] = $data['status'] ?? 'new';

        $serviceRequest = ServiceRequest::create($data);
        
        app(\App\Services\V1\NotificationService::class)->service_request($serviceRequest);
        
        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
            'message' => 'تم إنشاء الطلب بنجاح'
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/service-requests/{serviceRequest}',
        summary: 'عرض تفاصيل طلب',
        tags: ['Service Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'serviceRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'تفاصيل الطلب'),
            new OA\Response(response: 404, description: 'غير موجود')
        ]
    )]
    public function show(ServiceRequest $serviceRequest)
    {
        return new ServiceRequestResource($serviceRequest);
    }

    #[OA\Put(
        path: '/api/v1/service-requests/{serviceRequest}',
        summary: 'تحديث طلب',
        tags: ['Service Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'serviceRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'request_type', type: 'string', enum: ['package_request', 'contact_message']),
                    new OA\Property(property: 'package_name', type: 'string', nullable: true),
                    new OA\Property(property: 'customer_name', type: 'string'),
                    new OA\Property(property: 'company_name', type: 'string', nullable: true),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'subject', type: 'string', nullable: true),
                    new OA\Property(property: 'message', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['new', 'in_review', 'closed']),
                    new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'تم التحديث'),
            new OA\Response(response: 404, description: 'غير موجود')
        ]
    )]
    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $serviceRequest->update($request->validated());

        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
            'message' => 'تم تحديث الطلب بنجاح'
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/service-requests/{serviceRequest}',
        summary: 'حذف طلب (ناعم)',
        tags: ['Service Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'serviceRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'force', in: 'query', schema: new OA\Schema(type: 'boolean', default: false), description: 'حذف نهائي')
        ],
        responses: [
            new OA\Response(response: 200, description: 'تم الحذف'),
            new OA\Response(response: 404, description: 'غير موجود')
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::withTrashed()->findOrFail($id);

        if ($request->boolean('force')) {
            $serviceRequest->forceDelete();
            $message = 'تم حذف الطلب نهائياً';
        } else {
            $serviceRequest->delete();
            $message = 'تم نقل الطلب إلى سلة المهملات';
        }

        return response()->json(['message' => $message]);
    }

    #[OA\Post(
        path: '/api/v1/service-requests/{serviceRequest}/restore',
        summary: 'استعادة طلب محذوف',
        tags: ['Service Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'serviceRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'تمت الاستعادة'),
            new OA\Response(response: 404, description: 'غير موجود')
        ]
    )]
    public function restore($id)
    {
        $serviceRequest = ServiceRequest::withTrashed()->findOrFail($id);
        $serviceRequest->restore();

        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
            'message' => 'تم استعادة الطلب بنجاح'
        ]);
    }
}