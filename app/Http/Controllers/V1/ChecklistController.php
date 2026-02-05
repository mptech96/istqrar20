<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreChecklistRequest;
use App\Http\Requests\V1\UpdateChecklistRequest;
use App\Http\Resources\V1\ChecklistResource;
use App\Models\V1\Checklist;
use App\Models\V1\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ChecklistController extends Controller
{
    #[OA\Get(
        path: '/api/v1/checklists',
        operationId: 'getChecklists',
        summary: 'عرض قوائم المراجعة',
        description: 'استرجاع قائمة قوائم المراجعة مع إمكانية الفلترة والترتيب',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
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
                description: 'بحث بالاسم أو الوصف أو الكود',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'company_id',
                description: 'فلتر بمعرف الشركة (NULL للعامة)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'is_active',
                description: 'فلتر بالنشاط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'is_template',
                description: 'فلتر بالنماذج',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'industry_type',
                description: 'فلتر بنوع الصناعة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'ترتيب النتائج حسب',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'created_at',
                    enum: ['name', 'code', 'total_score', 'created_at']
                )
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'اتجاه الترتيب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'company,questions,creator')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة قوائم المراجعة',
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
                                    new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                    new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                    new OA\Property(property: 'version', type: 'string', example: '1.0'),
                                    new OA\Property(property: 'total_score', type: 'integer', example: 100),
                                    new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
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
        ]
    )]
    public function index(Request $request)
    {
        $query = Checklist::query();
        
        // Filtering
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('company_id')) {
            if ($request->input('company_id') === 'null') {
                $query->whereNull('company_id');
            } else {
                $query->where('company_id', $request->input('company_id'));
            }
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('is_template')) {
            $query->where('is_template', $request->boolean('is_template'));
        }
        
        if ($request->has('industry_type')) {
            $query->where('industry_type', $request->input('industry_type'));
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['company', 'questions', 'creator'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $checklists = $query->with($with)->paginate($request->input('per_page', 15));
        
        return (ChecklistResource::collection($checklists));
    }
    
    #[OA\Get(
        path: '/api/v1/checklists/industry-types',
        operationId: 'getChecklistIndustryTypes',
        summary: 'عرض أنواع الصناعة لقوائم المراجعة',
        description: 'استرجاع قائمة أنواع الصناعة الفريدة لقوائم المراجعة',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة أنواع الصناعة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'المطاعم')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function industryTypes()
    {
        $industryTypes = Checklist::distinct()
            ->whereNotNull('industry_type')
            ->pluck('industry_type')
            ->sort()
            ->values();
            
        return response()->json([
            'data' => $industryTypes
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/checklists',
        operationId: 'createChecklist',
        summary: 'إنشاء قائمة مراجعة جديدة',
        description: 'إنشاء قائمة مراجعة جديدة مع إعداداتها',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'total_score', 'passing_score'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                    new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                    new OA\Property(property: 'description', type: 'string', example: 'قائمة مراجعة شاملة للنظافة'),
                    new OA\Property(property: 'version', type: 'string', example: '1.0'),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'industry_type', type: 'string', example: 'المطاعم'),
                    new OA\Property(property: 'total_score', type: 'integer', example: 100),
                    new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                    new OA\Property(property: 'time_estimate', type: 'integer', example: 60),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_template', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء القائمة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                new OA\Property(property: 'version', type: 'string', example: '1.0'),
                                new OA\Property(property: 'total_score', type: 'integer', example: 100),
                                new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء القائمة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function store(StoreChecklistRequest $request)
    {
        $checklist = Checklist::create([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'version' => $request->input('version', '1.0'),
            'company_id' => $request->input('company_id'),
            'industry_type' => $request->input('industry_type'),
            'total_score' => $request->input('total_score'),
            'passing_score' => $request->input('passing_score'),
            'time_estimate' => $request->input('time_estimate'),
            'is_active' => $request->input('is_active', true),
            'is_template' => $request->input('is_template', false),
            'created_by' => Auth::id(),
        ]);
        
        return response()->json([
            'data' => new ChecklistResource($checklist),
            'message' => 'تم إنشاء القائمة بنجاح'
        ], 201);
    }
    
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}',
        operationId: 'getChecklist',
        summary: 'عرض تفاصيل قائمة مراجعة',
        description: 'استرجاع تفاصيل قائمة مراجعة مع أسئلتها',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'company,questions,creator')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل القائمة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                new OA\Property(property: 'description', type: 'string', example: 'قائمة مراجعة شاملة للنظافة'),
                                new OA\Property(property: 'version', type: 'string', example: '1.0'),
                                new OA\Property(property: 'company_id', type: 'integer', example: 1),
                                new OA\Property(property: 'industry_type', type: 'string', example: 'المطاعم'),
                                new OA\Property(property: 'total_score', type: 'integer', example: 100),
                                new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                                new OA\Property(property: 'time_estimate', type: 'integer', example: 60),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'is_template', type: 'boolean', example: false),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),

                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
        ]
    )]
    public function show(Checklist $checklist, Request $request)
    {
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['company', 'questions', 'creator'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $checklist->load($with);
        
        return response()->json([
            'data' => new ChecklistResource($checklist)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/checklists/{checklist}',
        operationId: 'updateChecklist',
        summary: 'تحديث بيانات قائمة مراجعة',
        description: 'تحديث بيانات قائمة مراجعة موجودة',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
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
                    new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة (محدثة)'),
                    new OA\Property(property: 'code', type: 'string', example: 'CL-001-A'),
                    new OA\Property(property: 'description', type: 'string', example: 'قائمة مراجعة شاملة للنظافة - محدثة'),
                    new OA\Property(property: 'version', type: 'string', example: '1.1'),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'industry_type', type: 'string', example: 'المطاعم والفنادق'),
                    new OA\Property(property: 'total_score', type: 'integer', example: 120),
                    new OA\Property(property: 'passing_score', type: 'integer', example: 80),
                    new OA\Property(property: 'time_estimate', type: 'integer', example: 75),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'is_template', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث القائمة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث القائمة بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة (محدثة)'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001-A'),
                                new OA\Property(property: 'version', type: 'string', example: '1.1'),
                                new OA\Property(property: 'total_score', type: 'integer', example: 120),
                                new OA\Property(property: 'passing_score', type: 'integer', example: 80),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(UpdateChecklistRequest $request, Checklist $checklist)
    {
        $checklist->update($request->only([
            'name', 'code', 'description', 'version',
            'company_id', 'industry_type', 'total_score',
            'passing_score', 'time_estimate', 'is_active', 'is_template'
        ]));
        
        return response()->json([
            'data' => new ChecklistResource($checklist->load(['company', 'questions'])),
            'message' => 'تم تحديث القائمة بنجاح'
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/checklists/{checklist}',
        operationId: 'deleteChecklist',
        summary: 'حذف قائمة مراجعة',
        description: 'حذف قائمة مراجعة مع أسئلتها (حذف ناعم)',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
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
                description: 'تم حذف القائمة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف القائمة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
        ]
    )]
    public function destroy(Checklist $checklist)
    {
        $checklist->delete();
        
        return response()->json([
            'data' => new ChecklistResource($checklist),
            'message' => 'تم حذف القائمة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/checklists/{checklist}/restore',
        operationId: 'restoreChecklist',
        summary: 'استعادة قائمة محذوفة',
        description: 'استعادة قائمة مراجعة تم حذفها (حذف ناعم)',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة القائمة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة القائمة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
        ]
    )]
    public function restore($id)
    {
        $checklist = Checklist::withTrashed()->findOrFail($id);
        $checklist->restore();
        
        return response()->json([
            'data' => new ChecklistResource($checklist),
            'message' => 'تم استعادة القائمة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/checklists/{checklist}/duplicate',
        operationId: 'duplicateChecklist',
        summary: 'نسخ قائمة مراجعة',
        description: 'إنشاء نسخة من قائمة مراجعة موجودة مع أسئلتها',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة الأصلية',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'new_name', type: 'string', example: 'نسخة من قائمة النظافة'),
                    new OA\Property(property: 'new_code', type: 'string', example: 'CL-001-COPY'),
                    new OA\Property(property: 'version', type: 'string', example: '1.0'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم نسخ القائمة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'name', type: 'string', example: 'نسخة من قائمة النظافة'),
                                new OA\Property(property: 'code', type: 'string', example: 'CL-001-COPY'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم نسخ القائمة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة الأصلية غير موجودة'
            ),
        ]
    )]
    public function duplicate(Checklist $checklist, Request $request)
    {
        $request->validate([
            'new_name' => ['required', 'string', 'max:255'],
            'new_code' => ['nullable', 'string', 'max:50', 'unique:checklists,code'],
            'version' => ['nullable', 'string', 'max:20'],
        ]);
        
        return DB::transaction(function () use ($checklist, $request) {
            // Create new checklist
            $newChecklist = $checklist->replicate();
            $newChecklist->name = $request->input('new_name');
            $newChecklist->code = $request->input('new_code');
            $newChecklist->version = $request->input('version', '1.0');
            $newChecklist->created_by = Auth::id();
            $newChecklist->save();
            
            // Duplicate questions
            foreach ($checklist->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->checklist_id = $newChecklist->id;
                $newQuestion->save();
            }
            
            return response()->json([
                'data' => new ChecklistResource($newChecklist->load('questions')),
                'message' => 'تم نسخ القائمة بنجاح'
            ], 201);
        });
    }
    
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}/statistics',
        operationId: 'getChecklistStatistics',
        summary: 'عرض إحصائيات قائمة مراجعة',
        description: 'استرجاع إحصائيات قائمة مراجعة (عدد الأسئلة، النقاط، إلخ)',
        tags: ['Checklists'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصائيات القائمة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_questions', type: 'integer', example: 25),
                        new OA\Property(property: 'active_questions', type: 'integer', example: 20),
                        new OA\Property(property: 'required_questions', type: 'integer', example: 15),
                        new OA\Property(property: 'critical_questions', type: 'integer', example: 5),
                        new OA\Property(property: 'total_max_score', type: 'integer', example: 250),
                        new OA\Property(property: 'average_weight', type: 'number', format: 'float', example: 4.2),
                        new OA\Property(property: 'question_types', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
        ]
    )]
    public function statistics(Checklist $checklist)
    {
        $checklist->load('questions');
        
        $questions = $checklist->questions;
        
        $questionTypes = $questions->groupBy('type')->map->count();
        
        $totalMaxScore = $questions->sum('max_score');
        $averageWeight = $questions->avg('weight') ?? 0;
        
        return response()->json([
            'total_questions' => $questions->count(),
            'active_questions' => $questions->where('is_active', true)->count(),
            'required_questions' => $questions->where('is_required', true)->count(),
            'critical_questions' => $questions->where('is_critical', true)->count(),
            'total_max_score' => $totalMaxScore,
            'average_weight' => round($averageWeight, 2),
            'question_types' => $questionTypes,
        ]);
    }
}
