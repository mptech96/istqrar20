<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCriteriaRequest;
use App\Http\Requests\V1\UpdateCriteriaRequest;
use App\Http\Resources\V1\CriteriaResource;
use App\Models\V1\Criteria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CriteriaController extends Controller
{
    #[OA\Get(
        path: '/api/v1/criterias',
        operationId: 'getCriterias',
        summary: 'عرض قائمة معايير التقييم',
        description: 'استرجاع قائمة معايير التقييم مع إمكانية الفلترة والترتيب',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'رقم الصفحة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'عدد العناصر في الصفحة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'search',
                description: 'بحث بالاسم أو الوصف',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category',
                description: 'فلتر بالفئة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'is_active',
                description: 'فلتر بالنشاط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'ترتيب النتائج حسب',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'sort_order',
                    enum: ['name', 'category', 'weight', 'sort_order', 'created_at']
                )
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'اتجاه الترتيب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'ranges,creator')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة معايير التقييم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                                    new OA\Property(property: 'category', type: 'string', example: 'النظافة'),
                                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 20.5),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'first', type: 'string'),
                                new OA\Property(property: 'last', type: 'string'),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ]
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'from', type: 'integer', nullable: true),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'path', type: 'string'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'to', type: 'integer', nullable: true),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به'
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول'
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Criteria::query();
        
        // Filtering
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['ranges', 'creator'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $criterias = $query->with($with)->paginate($request->input('per_page', 15));
        
        return CriteriaResource::collection($criterias);
    }
    
    #[OA\Get(
        path: '/api/v1/criterias/categories',
        operationId: 'getCriteriaCategories',
        summary: 'عرض فئات معايير التقييم',
        description: 'استرجاع قائمة الفئات الفريدة لمعايير التقييم',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة الفئات',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function categories()
    {
        $categories = Criteria::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();
            
        return response()->json([
            'data' => $categories
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/criterias',
        operationId: 'createCriteria',
        summary: 'إنشاء معيار تقييم جديد',
        description: 'إنشاء معيار تقييم جديد مع نطاقات التقييم الخاصة به',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'category', 'weight', 'ranges'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                    new OA\Property(property: 'description', type: 'string', example: 'تقييم مستوى النظافة في المنشأة'),
                    new OA\Property(property: 'category', type: 'string', example: 'النظافة'),
                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 20.5),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'ranges',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            type: 'object',
                            required: ['name', 'min_score', 'max_score'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'ممتاز'),
                                new OA\Property(property: 'min_score', type: 'integer', example: 90),
                                new OA\Property(property: 'max_score', type: 'integer', example: 100),
                                new OA\Property(property: 'color', type: 'string', example: '#28a745'),
                                new OA\Property(property: 'description', type: 'string', example: 'مستوى نظافة ممتاز'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء المعيار بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                                new OA\Property(property: 'category', type: 'string', example: 'النظافة'),
                                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 20.5),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'ranges',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'ممتاز'),
                                            new OA\Property(property: 'min_score', type: 'integer', example: 90),
                                            new OA\Property(property: 'max_score', type: 'integer', example: 100),
                                            new OA\Property(property: 'color', type: 'string', example: '#28a745'),
                                            new OA\Property(property: 'description', type: 'string', example: 'مستوى نظافة ممتاز'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء المعيار بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreCriteriaRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create criteria
            $criteria = Criteria::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'category' => $request->input('category'),
                'weight' => $request->input('weight'),
                'is_active' => $request->input('is_active', true),
                'sort_order' => $request->input('sort_order', 0),
                'created_by' => Auth::id(),
            ]);
            
            // Create criteria ranges
            $ranges = collect($request->input('ranges'))
                ->sortBy('min_score')
                ->values();
            
            foreach ($ranges as $range) {
                $criteria->ranges()->create([
                    'name' => $range['name'],
                    'min_score' => $range['min_score'],
                    'max_score' => $range['max_score'],
                    'color' => $range['color'] ?? $this->getDefaultColor($range),
                    'description' => $range['description'] ?? null,
                ]);
            }
            
            return response()->json([
                'data' => new CriteriaResource($criteria->load('ranges')),
                'message' => 'تم إنشاء المعيار بنجاح'
            ], 201);
        });
    }
    
    #[OA\Get(
        path: '/api/v1/criterias/{criteria}',
        operationId: 'getCriteria',
        summary: 'عرض تفاصيل معيار تقييم',
        description: 'استرجاع تفاصيل معيار تقييم مع نطاقات التقييم الخاصة به',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'criteria',
                description: 'معرف المعيار',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'ranges,creator')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل المعيار',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                                new OA\Property(property: 'description', type: 'string', example: 'تقييم مستوى النظافة في المنشأة'),
                                new OA\Property(property: 'category', type: 'string', example: 'النظافة'),
                                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 20.5),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'ranges',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'ممتاز'),
                                            new OA\Property(property: 'min_score', type: 'integer', example: 90),
                                            new OA\Property(property: 'max_score', type: 'integer', example: 100),
                                            new OA\Property(property: 'color', type: 'string', example: '#28a745'),
                                            new OA\Property(property: 'description', type: 'string', example: 'مستوى نظافة ممتاز'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المعيار غير موجود'
            ),
        ]
    )]
    public function show(Criteria $criteria, Request $request)
    {
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['ranges', 'creator'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $criteria->load($with);
        
        return response()->json([
            'data' => new CriteriaResource($criteria)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/criterias/{criteria}',
        operationId: 'updateCriteria',
        summary: 'تحديث بيانات معيار تقييم',
        description: 'تحديث بيانات معيار تقييم موجود',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'criteria',
                description: 'معرف المعيار',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة (محدث)'),
                    new OA\Property(property: 'description', type: 'string', example: 'تقييم مستوى النظافة في المنشأة - محدث'),
                    new OA\Property(property: 'category', type: 'string', example: 'النظافة والصحة'),
                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 25.0),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 2),
                    new OA\Property(
                        property: 'ranges',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            type: 'object',
                            required: ['name', 'min_score', 'max_score'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'ممتاز'),
                                new OA\Property(property: 'min_score', type: 'integer', example: 90),
                                new OA\Property(property: 'max_score', type: 'integer', example: 100),
                                new OA\Property(property: 'color', type: 'string', example: '#28a745'),
                                new OA\Property(property: 'description', type: 'string', example: 'مستوى نظافة ممتاز'),
                            ]
                        )
                    ),

                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث المعيار بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث المعيار بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة (محدث)'),
                                new OA\Property(property: 'category', type: 'string', example: 'النظافة والصحة'),
                                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 25.0),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'sort_order', type: 'integer', example: 2),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المعيار غير موجود'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(UpdateCriteriaRequest $request, Criteria $criteria)
    {
        return DB::transaction(function () use ($request, $criteria) {
            
            $criteria->update($request->only([
                'name', 'description', 'category', 'weight', 'is_active', 'sort_order'
            ]));
            $ranges = collect($request->input('ranges'))?->sortBy('min_score')?->values();
            
            if ($ranges && count($ranges) > 0) {
                foreach ($ranges as $range) {
                    $existing = $criteria->ranges()
                        ->withTrashed()
                        ->where('name', $range['name'])
                        ->first();
                    
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        
                        $existing->update([
                            'min_score' => $range['min_score'],
                            'max_score' => $range['max_score'],
                            'color' => $range['color'] ?? $this->getDefaultColor($range),
                            'description' => $range['description'] ?? null,
                        ]);
                    } else {
                        $criteria->ranges()->create([
                            'name' => $range['name'],
                            'min_score' => $range['min_score'],
                            'max_score' => $range['max_score'],
                            'color' => $range['color'] ?? $this->getDefaultColor($range),
                            'description' => $range['description'] ?? null,
                        ]);
                    }
                }
            }
            return response()->json([
                'data' => new CriteriaResource($criteria->load('ranges')),
                'message' => 'تم تحديث المعيار بنجاح'
            ]);
        });
    }
    
    #[OA\Delete(
        path: '/api/v1/criterias/{criteria}',
        operationId: 'deleteCriteria',
        summary: 'حذف معيار تقييم',
        description: 'حذف معيار تقييم مع نطاقات التقييم الخاصة به (حذف ناعم)',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'criteria',
                description: 'معرف المعيار',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'force',
                description: 'حذف نهائي',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف المعيار بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف المعيار بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المعيار غير موجود'
            ),
        ]
    )]
    public function destroy(Criteria $criteria)
    {
        $criteria->delete();
        
        return response()->json([
            'data' => new CriteriaResource($criteria),
            'message' => 'تم حذف المعيار بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/criterias/{criteria}/restore',
        operationId: 'restoreCriteria',
        summary: 'استعادة معيار محذوف',
        description: 'استعادة معيار تقييم تم حذفه (حذف ناعم)',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'criteria',
                description: 'معرف المعيار',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة المعيار بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة المعيار بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'النظافة العامة'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المعيار غير موجود'
            ),
        ]
    )]
    public function restore($id)
    {
        $criteria = Criteria::withTrashed()->findOrFail($id);
        $criteria->restore();
        
        return response()->json([
            'data' => new CriteriaResource($criteria),
            'message' => 'تم استعادة المعيار بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/criterias/{criteria}/evaluate',
        operationId: 'evaluateCriteriaScore',
        summary: 'تقييم درجة بناء على معيار',
        description: 'تحديد نطاق التقييم المناسب لدرجة معينة بناء على معيار معين',
        tags: ['Criterias'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'criteria',
                description: 'معرف المعيار',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'score',
                description: 'الدرجة المراد تقييمها',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 100, example: 85)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'نتيجة التقييم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'criteria_id', type: 'integer', example: 1),
                        new OA\Property(property: 'criteria_name', type: 'string', example: 'النظافة العامة'),
                        new OA\Property(property: 'score', type: 'integer', example: 85),
                        new OA\Property(property: 'range_id', type: 'integer', example: 3),
                        new OA\Property(property: 'range_name', type: 'string', example: 'جيد جداً'),
                        new OA\Property(property: 'range_color', type: 'string', example: '#17a2b8'),
                        new OA\Property(property: 'range_description', type: 'string', example: 'مستوى نظافة جيد جداً'),
                        new OA\Property(property: 'weighted_score', type: 'number', format: 'float', example: 17.0),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المعيار غير موجود'
            ),
            new OA\Response(
                response: 400,
                description: 'الدرجة خارج النطاق'
            ),
        ]
    )]
    public function evaluate(Criteria $criteria, Request $request)
    {
        $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        
        $score = $request->input('score');
        
        // Find the appropriate range for the score
        $range = $criteria->ranges()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
        
        if (!$range) {
            return response()->json([
                'message' => 'الدرجة خارج نطاق التقييم لهذا المعيار'
            ], 400);
        }
        
        // Calculate weighted score
        $weightedScore = ($score / 100) * $criteria->weight;
        
        return response()->json([
            'criteria_id' => $criteria->id,
            'criteria_name' => $criteria->name,
            'score' => $score,
            'range_id' => $range->id,
            'range_name' => $range->name,
            'range_color' => $range->color,
            'range_description' => $range->description,
            'weighted_score' => round($weightedScore, 2),
        ]);
    }
    
    /**
     * Get default color based on score range
     */
    private function getDefaultColor(array $range): string
    {
        $avgScore = ($range['min_score'] + $range['max_score']) / 2;
        
        return match (true) {
            $avgScore >= 90 => '#28a745', // Green - Excellent
            $avgScore >= 80 => '#17a2b8', // Blue - Very Good
            $avgScore >= 70 => '#ffc107', // Yellow - Good
            $avgScore >= 60 => '#fd7e14', // Orange - Acceptable
            default => '#dc3545', // Red - Poor
        };
    }
}