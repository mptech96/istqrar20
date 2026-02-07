<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreVisitRequest;
use App\Http\Requests\V1\UpdateVisitRequest;
use App\Http\Requests\V1\StartVisitRequest;
use App\Http\Requests\V1\CompleteVisitRequest;
use App\Http\Requests\V1\ApproveVisitRequest;
use App\Http\Requests\V1\RejectVisitRequest;
use App\Http\Resources\V1\VisitResource;
use App\Http\Resources\V1\QuestionResource;
use App\Models\V1\Visit;
use App\Models\V1\Branch;
use App\Models\V1\Checklist;
use App\Models\V1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class VisitController extends Controller
{
    #[OA\Get(
        path: '/api/v1/visits',
        operationId: 'getVisits',
        summary: 'عرض الزيارات',
        description: 'استرجاع قائمة الزيارات مع إمكانية الفلترة والترتيب',
        tags: ['Visits'],
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
                description: 'بحث في الملاحظات',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'branch_id',
                description: 'فلتر بمعرف الفرع',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'auditor_id',
                description: 'فلتر بمعرف المراقب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'checklist_id',
                description: 'فلتر بمعرف قائمة المراجعة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة الزيارة',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved', 'rejected']
                )
            ),
            new OA\Parameter(
                name: 'category',
                description: 'فلتر بفئة الزيارة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['A', 'B', 'C', 'D', 'F'])
            ),
            new OA\Parameter(
                name: 'from_date',
                description: 'من تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                description: 'إلى تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'ترتيب النتائج حسب',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'created_at',
                    enum: ['scheduled_date', 'visit_date', 'total_score', 'percentage', 'created_at']
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
                schema: new OA\Schema(type: 'string', example: 'branch,auditor,checklist,questions')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة الزيارات',
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
                                    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                    new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10'),
                                    new OA\Property(property: 'scheduled_time', type: 'string', example: '09:00'),
                                    new OA\Property(property: 'actual_start_time', type: 'string', format: 'date-time', example: '2026-02-10T09:05:00Z'),
                                    new OA\Property(property: 'actual_end_time', type: 'string', format: 'date-time', example: '2026-02-10T10:15:00Z'),
                                    new OA\Property(property: 'visit_date', type: 'string', format: 'date', example: '2026-02-10'),
                                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                    new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                    new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'category', type: 'string', example: 'B'),
                                    new OA\Property(property: 'category_text', type: 'string', example: 'جيد جداً (80% - 89%)'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-05T10:30:00Z'),
                                    new OA\Property(
                                        property: 'branch',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                            new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'auditor',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 2),
                                            new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                            new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'links',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', example: 'http://example.com/api/v1/visits?page=1'),
                                new OA\Property(property: 'last', type: 'string', example: 'http://example.com/api/v1/visits?page=5'),
                                new OA\Property(property: 'prev', type: 'string', nullable: true, example: 'http://example.com/api/v1/visits?page=1'),
                                new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://example.com/api/v1/visits?page=3'),
                            ]
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 2),
                                new OA\Property(property: 'from', type: 'integer', example: 16),
                                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                new OA\Property(property: 'path', type: 'string', example: 'http://example.com/api/v1/visits'),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'to', type: 'integer', example: 30),
                                new OA\Property(property: 'total', type: 'integer', example: 72),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Visit::query();
        
        // Filtering
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('auditor_notes', 'like', "%{$search}%")
                  ->orWhere('branch_feedback', 'like', "%{$search}%")
                  ->orWhere('internal_notes', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        
        if ($request->has('auditor_id')) {
            $query->where('auditor_id', $request->input('auditor_id'));
        }
        
        if ($request->has('checklist_id')) {
            $query->where('checklist_id', $request->input('checklist_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->has('from_date')) {
            $query->where('scheduled_date', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->where('scheduled_date', '<=', $request->input('to_date'));
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
                if (in_array(trim($include), ['branch', 'auditor', 'checklist', 'questions', 'attachments', 'approver'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $visits = $query->with($with)->paginate($request->input('per_page', 15));
        
        return (VisitResource::collection($visits));
    }
    
    #[OA\Get(
        path: '/api/v1/visits/calendar',
        operationId: 'getVisitsCalendar',
        summary: 'عرض زيارات التقويم',
        description: 'استرجاع الزيارات بصيغة مناسبة للتقويم',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'month',
                description: 'الشهر (YYYY-MM)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-02')
            ),
            new OA\Parameter(
                name: 'auditor_id',
                description: 'فلتر بمعرف المراقب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'branch_id',
                description: 'فلتر بمعرف الفرع',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'زيارات التقويم',
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
                                    new OA\Property(property: 'title', type: 'string', example: 'الفرع الرئيسي - أحمد محمد'),
                                    new OA\Property(property: 'start', type: 'string', example: '2026-02-10T09:00:00'),
                                    new OA\Property(property: 'end', type: 'string', example: '2026-02-10T10:00:00'),
                                    new OA\Property(property: 'color', type: 'string', example: '#3498db'),
                                    new OA\Property(
                                        property: 'extendedProps',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'branch_name', type: 'string', example: 'الفرع الرئيسي'),
                                            new OA\Property(property: 'auditor_name', type: 'string', example: 'أحمد محمد'),
                                            new OA\Property(property: 'checklist_name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                            new OA\Property(property: 'status', type: 'string', example: 'scheduled'),
                                            new OA\Property(property: 'status_text', type: 'string', example: 'مجدولة'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(property: 'month', type: 'string', example: '2026-02'),
                        new OA\Property(property: 'month_name', type: 'string', example: 'فبراير 2026'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function calendar(Request $request)
    {
        $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);
        
        $query = Visit::query();
        $month = Carbon::parse($request->input('month'));
        
        $query->whereBetween('scheduled_date', [
            $month->startOfMonth(),
            $month->endOfMonth()
        ]);
        
        if ($request->has('auditor_id')) {
            $query->where('auditor_id', $request->input('auditor_id'));
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        
        $visits = $query->with(['branch', 'auditor', 'checklist'])->get();
        
        $calendarData = $visits->map(function ($visit) {
            return [
                'id' => $visit->id,
                'title' => $visit->branch->name ?? 'غير محدد',
                'start' => $visit->scheduled_date . ($visit->scheduled_time ? 'T' . $visit->scheduled_time : ''),
                'end' => $visit->estimated_duration ? 
                    Carbon::parse($visit->scheduled_date . ' ' . $visit->scheduled_time)
                        ->addMinutes($visit->estimated_duration)
                        ->toIso8601String() : null,
                'color' => $this->getStatusColor($visit->status),
                'extendedProps' => [
                    'branch_name' => $visit->branch->name ?? 'غير محدد',
                    'auditor_name' => $visit->auditor->name ?? 'غير محدد',
                    'checklist_name' => $visit->checklist->name ?? 'غير محدد',
                    'status' => $visit->status,
                    'status_text' => $visit->getStatusText(),
                ],
            ];
        });
        
        return response()->json([
            'data' => $calendarData,
            'month' => $month->format('Y-m'),
            'month_name' => $month->translatedFormat('F Y'),
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/visits/create',
        operationId: 'getBranchesAuditorsChecklists',
        summary: 'بيانات انشاء زيارة',
        description: 'عرض الفروع والمراقبين والقوائم',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'branches',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'فرع 1'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'auditors',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'محمد'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'checklists',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'قائمة 1'),
                                ]
                            )
                        )

                    ]
                )
            ),
        ]
    )]
    public function create()
    {
        return response()->json([
            'branches' => Branch::select(['id', 'name'])->get(),
            'auditors' => User::select(['id', 'name'])->get(),
            'checklists' => Checklist::select(['id', 'name'])->get(),
        ]);
    }
    
    
    #[OA\Post(
        path: '/api/v1/visits',
        operationId: 'createVisit',
        summary: 'إنشاء زيارة جديدة',
        description: 'إنشاء زيارة جديدة مع تحديد الفرع والمراقب وقائمة المراجعة
        <br>
        استخدم نقطة 
        <br>
        /api/v1/visits/create
        <br>
        لعرض بيانات الفروع والمراقبين وقوائم المراجعة
        ',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['branch_id', 'auditor_id', 'checklist_id', 'scheduled_date'],
                properties: [
                    new OA\Property(property: 'branch_id', type: 'integer', example: 1, description: 'معرف الفرع'),
                    new OA\Property(property: 'auditor_id', type: 'integer', example: 2, description: 'معرف المراقب'),
                    new OA\Property(property: 'checklist_id', type: 'integer', example: 1, description: 'معرف قائمة المراجعة'),
                    new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10', description: 'التاريخ المحدد'),
                    new OA\Property(property: 'scheduled_time', type: 'string', format: 'time', example: '09:00', description: 'الوقت المحدد'),
                    new OA\Property(property: 'estimated_duration', type: 'integer', example: 60, description: 'المدة المقدرة بالدقائق'),
                    new OA\Property(property: 'internal_notes', type: 'string', example: 'ملاحظات داخلية للزيارة', description: 'ملاحظات داخلية'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'scheduled_time', type: 'string', example: '09:00'),
                                new OA\Property(property: 'estimated_duration', type: 'integer', example: 60),
                                new OA\Property(property: 'status', type: 'string', example: 'scheduled'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'مجدولة'),
                                new OA\Property(property: 'max_possible_score', type: 'integer', example: 100),
                                new OA\Property(property: 'internal_notes', type: 'string', example: 'ملاحظات داخلية للزيارة'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-05T10:30:00Z'),
                                new OA\Property(
                                    property: 'branch',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                        new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'auditor',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                        new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'checklist',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                        new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                        new OA\Property(property: 'total_score', type: 'integer', example: 100),
                                        new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                                    ]
                                ),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل التاريخ المحدد مطلوب')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreVisitRequest $request)
    {
        $visit = Visit::create([
            'branch_id' => $request->input('branch_id'),
            'auditor_id' => $request->input('auditor_id'),
            'checklist_id' => $request->input('checklist_id'),
            'scheduled_date' => $request->input('scheduled_date'),
            'scheduled_time' => $request->input('scheduled_time'),
            'estimated_duration' => $request->input('estimated_duration', Checklist::find($request->input('checklist_id'))?->time_estimate),
            'internal_notes' => $request->input('internal_notes'),
            'status' => 'scheduled',
            'max_possible_score' => Checklist::find($request->input('checklist_id'))->total_score ?? 100,
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit->load(['branch', 'auditor', 'checklist'])),
            'message' => 'تم إنشاء الزيارة بنجاح'
        ], 201);
    }
    
    #[OA\Get(
        path: '/api/v1/visits/{visit}',
        operationId: 'getVisit',
        summary: 'عرض تفاصيل زيارة',
        description: 'استرجاع تفاصيل زيارة معينة مع علاقاتها',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'branch,auditor,checklist,questions,attachments')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'scheduled_time', type: 'string', example: '09:00'),
                                new OA\Property(property: 'actual_start_time', type: 'string', format: 'date-time', example: '2026-02-10T09:05:00Z'),
                                new OA\Property(property: 'actual_end_time', type: 'string', format: 'date-time', example: '2026-02-10T10:15:00Z'),
                                new OA\Property(property: 'visit_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', example: '2026-02-10T10:20:00Z'),
                                new OA\Property(property: 'estimated_duration', type: 'integer', example: 60),
                                new OA\Property(property: 'actual_duration', type: 'integer', example: 70),
                                new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                new OA\Property(property: 'max_possible_score', type: 'integer', example: 100),
                                new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                new OA\Property(property: 'category', type: 'string', example: 'B'),
                                new OA\Property(property: 'category_text', type: 'string', example: 'جيد جداً (80% - 89%)'),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                new OA\Property(property: 'approved_by', type: 'integer', example: 3),
                                new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                                new OA\Property(property: 'rejection_reason', type: 'string', example: 'سبب الرفض إذا كان موجوداً'),
                                new OA\Property(property: 'auditor_notes', type: 'string', example: 'ملاحظات المراقب'),
                                new OA\Property(property: 'branch_feedback', type: 'string', example: 'ملاحظات الفرع'),
                                new OA\Property(property: 'internal_notes', type: 'string', example: 'ملاحظات داخلية'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-05T10:30:00Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                                new OA\Property(
                                    property: 'branch',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                        new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                                        new OA\Property(property: 'address', type: 'string', example: 'شارع الملك فهد، الرياض'),
                                        new OA\Property(property: 'phone', type: 'string', example: '+966112345678'),
                                        new OA\Property(property: 'email', type: 'string', example: 'main@branch.com'),
                                        new OA\Property(property: 'manager_name', type: 'string', example: 'محمد أحمد'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'auditor',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                        new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                                        new OA\Property(property: 'phone', type: 'string', example: '+966501234567'),
                                        new OA\Property(property: 'role', type: 'string', example: 'مراقب'),
                                        new OA\Property(property: 'department', type: 'string', example: 'مراقبة الجودة'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'checklist',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                        new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                        new OA\Property(property: 'description', type: 'string', example: 'قائمة مراجعة شاملة للنظافة'),
                                        new OA\Property(property: 'total_score', type: 'integer', example: 100),
                                        new OA\Property(property: 'passing_score', type: 'integer', example: 70),
                                        new OA\Property(property: 'time_estimate', type: 'integer', example: 60),
                                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'approver',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 3),
                                        new OA\Property(property: 'name', type: 'string', example: 'سالم علي'),
                                        new OA\Property(property: 'email', type: 'string', example: 'salem@example.com'),
                                        new OA\Property(property: 'role', type: 'string', example: 'مشرف'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'questions',
                                    type: 'array',
                                    nullable: true,
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                            new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                            new OA\Property(property: 'weight', type: 'integer', example: 5),
                                            new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                            new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                            new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                                            new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                        
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    nullable: true,
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'original_name', type: 'string', example: 'صورة_الفرع.jpg'),
                                            new OA\Property(property: 'file_url', type: 'string', example: 'http://example.com/storage/attachments/uuid.jpg'),
                                            new OA\Property(property: 'file_type', type: 'string', example: 'image'),
                                            new OA\Property(property: 'file_type_label', type: 'string', example: 'صورة'),
                                            new OA\Property(property: 'file_size', type: 'integer', example: 2048576),
                                            new OA\Property(property: 'title', type: 'string', example: 'صورة للفرع الرئيسي'),
                                            new OA\Property(property: 'description', type: 'string', example: 'صورة توضح حالة الفرع'),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'answers_count', type: 'integer', example: 15),
                                new OA\Property(property: 'completed_answers_count', type: 'integer', example: 15),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function show(Visit $visit, Request $request)
    {
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['branch', 'auditor', 'checklist', 'questions', 'attachments', 'approver'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $visit->load($with);
        
        return response()->json([
            'data' => new VisitResource($visit)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/visits/{visit}',
        operationId: 'updateVisit',
        summary: 'تحديث بيانات زيارة',
        description: 'تحديث بيانات زيارة موجودة',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
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
                    new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-12', description: 'التاريخ المحدد'),
                    new OA\Property(property: 'scheduled_time', type: 'string', format: 'time', example: '10:00', description: 'الوقت المحدد'),
                    new OA\Property(property: 'estimated_duration', type: 'integer', example: 75, description: 'المدة المقدرة بالدقائق'),
                    new OA\Property(property: 'auditor_notes', type: 'string', example: 'ملاحظات المراقب المحدثة', description: 'ملاحظات المراقب'),
                    new OA\Property(property: 'branch_feedback', type: 'string', example: 'ملاحظات الفرع المحدثة', description: 'ملاحظات الفرع'),
                    new OA\Property(property: 'internal_notes', type: 'string', example: 'ملاحظات داخلية محدثة', description: 'ملاحظات داخلية'),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'under_review',
                        description: 'حالة الزيارة',
                        enum: ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved', 'rejected']
                    ),
                    //new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 90.0, description: 'الدرجة الكلية'),
                    //new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 90.0, description: 'النسبة المئوية'),
                    /*new OA\Property(
                        property: 'category',
                        type: 'string',
                        example: 'A',
                        description: 'فئة الزيارة',
                        enum: ['A', 'B', 'C', 'D', 'F']
                    ),*/
                    /*new OA\Property(property: 'approved_by', type: 'integer', example: 3, description: 'معرف الموافق'),*/
                    new OA\Property(property: 'rejection_reason', type: 'string', example: 'سبب الرفض', description: 'سبب الرفض'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-12'),
                                new OA\Property(property: 'scheduled_time', type: 'string', example: '10:00'),
                                new OA\Property(property: 'status', type: 'string', example: 'under_review'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'قيد المراجعة'),
                                new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 90.0),
                                new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 90.0),
                                new OA\Property(property: 'category', type: 'string', example: 'A'),
                                new OA\Property(property: 'category_text', type: 'string', example: 'ممتاز (90% فما فوق)'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-05T14:45:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل الحالة غير صالح')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateVisitRequest $request, Visit $visit)
    {
        $visit->update($request->only([
            'scheduled_date', 'scheduled_time', 'estimated_duration',
            'auditor_notes', 'branch_feedback', 'internal_notes',
            'status', //'total_score', 'percentage', 'category',
            //'approved_by', 
            'rejection_reason'
        ]));
        
        return response()->json([
            'data' => new VisitResource($visit->load(['branch', 'auditor', 'checklist'])),
            'message' => 'تم تحديث الزيارة بنجاح'
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/visits/{visit}',
        operationId: 'deleteVisit',
        summary: 'حذف زيارة',
        description: 'حذف زيارة (حذف ناعم)',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                new OA\Property(property: 'status', type: 'string', example: 'cancelled'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'ملغاة'),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', example: '2026-02-05T15:00:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(Visit $visit)
    {
        $visit->delete();
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم حذف الزيارة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/restore',
        operationId: 'restoreVisit',
        summary: 'استعادة زيارة محذوفة',
        description: 'استعادة زيارة تم حذفها (حذف ناعم)',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                new OA\Property(property: 'status', type: 'string', example: 'scheduled'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'مجدولة'),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', example: null),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function restore($id)
    {
        $visit = Visit::withTrashed()->findOrFail($id);
        $visit->restore();
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم استعادة الزيارة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/start',
        operationId: 'startVisit',
        summary: 'بدء الزيارة',
        description: 'تسجيل وقت بدء الزيارة الفعلي',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        /*requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'visit_date', type: 'string', format: 'date', example: '2026-02-10', description: 'تاريخ الزيارة الفعلي'),
                ]
            )
        ),*/
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم بدء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'in_progress'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'قيد التنفيذ'),
                                new OA\Property(property: 'actual_start_time', type: 'string', format: 'date-time', example: '2026-02-10T09:05:00Z'),
                                new OA\Property(property: 'visit_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T09:05:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم بدء الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن بدء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن بدء الزيارة في حالتها الحالية'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function start(Visit $visit)
    {
        
        if ($visit->actual_start_time) {
            $endTime = Carbon::parse($visit->actual_start_time)
            ->addMinutes($visit->estimated_duration);
            if ($endTime->isPast()){
                return response()->json([
                    'message' => __tr('The evaluation period has ended.')
                ], 400);
            }
        }
        
        if (!in_array($visit->status, ['scheduled', 'draft'])) {
            return response()->json([
                'message' => 'لا يمكن بدء الزيارة في حالتها الحالية'
            ], 400);
        }
        
        if ($visit->scheduled_date->toDateString() != now()->toDateString()) {
            return response()->json([
                'message' => 'This action cannot be completed. The scheduled visit date must be today.'
            ], 400);
        }
        
        $visit->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
            'visit_date' => now()->toDateString(),
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم بدء الزيارة بنجاح'
        ]);
    }
    #[OA\Post(
        path: '/api/v1/visits/{visit}/complete',
        operationId: 'completeVisit',
        summary: 'إنهاء الزيارة',
        description: 'تسجيل وقت انتهاء الزيارة وحساب المدة الفعلية',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'auditor_notes', type: 'string', example: 'تمت الزيارة بنجاح', description: 'ملاحظات المراقب'),
                    /*new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5, description: 'الدرجة الكلية'),
                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5, description: 'النسبة المئوية'),
                    new OA\Property(
                        property: 'category',
                        type: 'string',
                        example: 'B',
                        description: 'فئة الزيارة',
                        enum: ['A', 'B', 'C', 'D', 'F']
                    ),
                    */
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إنهاء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                new OA\Property(property: 'actual_end_time', type: 'string', format: 'date-time', example: '2026-02-10T10:15:00Z'),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', example: '2026-02-10T10:15:00Z'),
                                new OA\Property(property: 'actual_duration', type: 'integer', example: 70),
                                new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                new OA\Property(property: 'category', type: 'string', example: 'B'),
                                new OA\Property(property: 'category_text', type: 'string', example: 'جيد جداً (80% - 89%)'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T10:15:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنهاء الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن إنهاء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إنهاء الزيارة إلا إذا كانت قيد التنفيذ'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل الفئة غير صالح')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function complete(Visit $visit, CompleteVisitRequest $request)
    {
        if ($visit->status !== 'in_progress') {
            return response()->json([
                'message' => 'لا يمكن إنهاء الزيارة إلا إذا كانت قيد التنفيذ'
            ], 400);
        }
        
        $visit->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'completed_at' => now(),
            'auditor_notes' => $request->input('auditor_notes', $visit->auditor_notes),
            //'total_score' => $request->input('total_score', $visit->total_score),
            //'percentage' => $request->input('percentage', $visit->percentage),
            //'category' => $request->input('category', $visit->category),
        ]);
        
        // حساب المدة الفعلية
        if ($visit->actual_start_time) {
            $duration = $visit->actual_start_time->diffInMinutes($visit->actual_end_time);
            $visit->update(['actual_duration' => $duration]);
        }
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم إنهاء الزيارة بنجاح'
        ]);
    }
    #[OA\Get(
        path: '/api/v1/visits/{visit}/questions',
        operationId: 'submitVisit',
        summary: 'اسئلة الزيارة',
        description: 'عرض اسئلة الزيارة',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'اسئلة الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question', type: 'string', example: 'under_review'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'questions'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]

    public function questions(Visit $visit)
    {
        return QuestionResource::collection($visit->questions()->paginate(request()->query('per_page', config('settings.per_page')), ['*'], 'page', request()->query('page', 1)));
    }
    
    /*
    #[OA\Post(
        path: '/api/v1/visits/{visit}/submit',
        operationId: 'submitVisit',
        summary: 'رفع الزيارة للمراجعة',
        description: 'رفع الزيارة المكتملة للمراجعة والاعتماد',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم رفع الزيارة للمراجعة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'under_review'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'قيد المراجعة'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T10:30:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم رفع الزيارة للمراجعة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن رفع الزيارة للمراجعة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن رفع الزيارة للمراجعة إلا إذا كانت مكتملة'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function submit(Visit $visit)
    {
        if ($visit->status !== 'completed') {
            return response()->json([
                'message' => 'لا يمكن رفع الزيارة للمراجعة إلا إذا كانت مكتملة'
            ], 400);
        }
        
        $visit->update([
            'status' => 'under_review',
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم رفع الزيارة للمراجعة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/approve',
        operationId: 'approveVisit',
        summary: 'اعتماد الزيارة',
        description: 'اعتماد الزيارة بعد المراجعة',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'تم الاعتماد بعد المراجعة', description: 'ملاحظات الاعتماد'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم اعتماد الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'approved'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'معتمدة'),
                                new OA\Property(property: 'approved_by', type: 'integer', example: 3),
                                new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                                new OA\Property(property: 'internal_notes', type: 'string', example: 'تم الاعتماد بعد المراجعة'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم اعتماد الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن اعتماد الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن اعتماد الزيارة في حالتها الحالية'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function approve(Visit $visit, ApproveVisitRequest $request)
    {
        if (!in_array($visit->status, ['under_review', 'completed'])) {
            return response()->json([
                'message' => 'لا يمكن اعتماد الزيارة في حالتها الحالية'
            ], 400);
        }
        
        $visit->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'internal_notes' => $request->input('notes') ? 
                ($visit->internal_notes . "\nملاحظات الاعتماد: " . $request->input('notes')) : 
                $visit->internal_notes,
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم اعتماد الزيارة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/reject',
        operationId: 'rejectVisit',
        summary: 'رفض الزيارة',
        description: 'رفض الزيارة بعد المراجعة',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['rejection_reason'],
                properties: [
                    new OA\Property(property: 'rejection_reason', type: 'string', example: 'عدم اكتمال جميع الإجابات', description: 'سبب الرفض'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم رفض الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'rejected'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'مرفوضة'),
                                new OA\Property(property: 'rejection_reason', type: 'string', example: 'عدم اكتمال جميع الإجابات'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم رفض الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن رفض الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن رفض الزيارة في حالتها الحالية'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل سبب الرفض مطلوب')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function reject(Visit $visit, RejectVisitRequest $request)
    {
        if (!in_array($visit->status, ['under_review', 'completed'])) {
            return response()->json([
                'message' => 'لا يمكن رفض الزيارة في حالتها الحالية'
            ], 400);
        }
        
        $visit->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم رفض الزيارة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/cancel',
        operationId: 'cancelVisit',
        summary: 'إلغاء الزيارة',
        description: 'إلغاء زيارة مجدولة أو قيد التنفيذ',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'عدم توفر المراقب', description: 'سبب الإلغاء'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إلغاء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'cancelled'),
                                new OA\Property(property: 'status_text', type: 'string', example: 'ملغاة'),
                                new OA\Property(property: 'internal_notes', type: 'string', example: 'سبب الإلغاء: عدم توفر المراقب'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T08:00:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إلغاء الزيارة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن إلغاء الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إلغاء الزيارة في حالتها الحالية'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function cancel(Visit $visit, Request $request)
    {
        if (!in_array($visit->status, ['scheduled', 'in_progress', 'draft'])) {
            return response()->json([
                'message' => 'لا يمكن إلغاء الزيارة في حالتها الحالية'
            ], 400);
        }
        
        $visit->update([
            'status' => 'cancelled',
            'internal_notes' => $request->input('reason') ? 
                ($visit->internal_notes . "\nسبب الإلغاء: " . $request->input('reason')) : 
                $visit->internal_notes,
        ]);
        
        return response()->json([
            'data' => new VisitResource($visit),
            'message' => 'تم إلغاء الزيارة بنجاح'
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/visits/{visit}/statistics',
        operationId: 'getVisitStatistics',
        summary: 'عرض إحصائيات الزيارة',
        description: 'استرجاع إحصائيات مفصلة عن زيارة معينة',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصائيات الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_questions', type: 'integer', example: 25),
                        new OA\Property(property: 'answered_questions', type: 'integer', example: 20),
                        new OA\Property(property: 'pending_questions', type: 'integer', example: 5),
                        new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 185.5),
                        new OA\Property(property: 'max_possible_score', type: 'integer', example: 250),
                        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 74.2),
                        new OA\Property(property: 'category', type: 'string', example: 'C'),
                        new OA\Property(property: 'critical_score', type: 'number', format: 'float', example: 45.0),
                        new OA\Property(property: 'critical_max_score', type: 'integer', example: 50),
                        new OA\Property(property: 'critical_percentage', type: 'number', format: 'float', example: 90.0),
                        new OA\Property(
                            property: 'correction_stats',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'pending', type: 'integer', example: 3),
                                new OA\Property(property: 'in_progress', type: 'integer', example: 2),
                                new OA\Property(property: 'completed', type: 'integer', example: 5),
                                new OA\Property(property: 'verified', type: 'integer', example: 8),
                                new OA\Property(property: 'waived', type: 'integer', example: 1),
                            ]
                        ),
                        new OA\Property(
                            property: 'duration_stats',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'estimated', type: 'integer', example: 60),
                                new OA\Property(property: 'actual', type: 'integer', example: 70),
                                new OA\Property(property: 'difference', type: 'integer', example: 10),
                            ]
                        ),
                        new OA\Property(
                            property: 'score_distribution',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'excellent', type: 'integer', example: 8),
                                new OA\Property(property: 'good', type: 'integer', example: 7),
                                new OA\Property(property: 'average', type: 'integer', example: 3),
                                new OA\Property(property: 'poor', type: 'integer', example: 2),
                                new OA\Property(property: 'failed', type: 'integer', example: 0),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function statistics(Visit $visit)
    {
        $visit->load('answers.question');
        
        $answers = $visit->answers;
        
        $correctionStats = $answers->groupBy('correction_status')->map->count();
        
        $totalQuestions = $visit->checklist->questions()->count() ?? 0;
        $answeredQuestions = $answers->count();
        $pendingQuestions = $totalQuestions - $answeredQuestions;
        
        $criticalQuestions = $answers->filter(function ($answer) {
            return $answer->question && $answer->question->is_critical;
        });
        
        $criticalScore = $criticalQuestions->sum('score');
        $criticalMaxScore = $criticalQuestions->sum('max_score');
        $criticalPercentage = $criticalMaxScore > 0 ? ($criticalScore / $criticalMaxScore) * 100 : 0;
        
        // توزيع الدرجات
        $scoreDistribution = [
            'excellent' => $answers->filter(fn($a) => $a->max_score > 0 && ($a->score / $a->max_score) >= 0.9)->count(),
            'good' => $answers->filter(fn($a) => $a->max_score > 0 && ($a->score / $a->max_score) >= 0.7 && ($a->score / $a->max_score) < 0.9)->count(),
            'average' => $answers->filter(fn($a) => $a->max_score > 0 && ($a->score / $a->max_score) >= 0.5 && ($a->score / $a->max_score) < 0.7)->count(),
            'poor' => $answers->filter(fn($a) => $a->max_score > 0 && ($a->score / $a->max_score) >= 0.3 && ($a->score / $a->max_score) < 0.5)->count(),
            'failed' => $answers->filter(fn($a) => $a->max_score > 0 && ($a->score / $a->max_score) < 0.3)->count(),
        ];
        
        return response()->json([
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'pending_questions' => $pendingQuestions,
            'total_score' => (float) $visit->total_score,
            'max_possible_score' => (int) $visit->max_possible_score,
            'percentage' => (float) $visit->percentage,
            'category' => $visit->category,
            'critical_score' => $criticalScore,
            'critical_max_score' => $criticalMaxScore,
            'critical_percentage' => round($criticalPercentage, 2),
            'correction_stats' => $correctionStats,
            'duration_stats' => [
                'estimated' => $visit->estimated_duration,
                'actual' => $visit->actual_duration,
                'difference' => $visit->actual_duration ? $visit->actual_duration - $visit->estimated_duration : null,
            ],
            'score_distribution' => $scoreDistribution,
        ]);
    }
    */
    #[OA\Get(
        path: '/api/v1/visits/auditor/{auditorId}',
        operationId: 'getAuditorVisits',
        summary: 'عرض زيارات مراقب',
        description: 'استرجاع جميع زيارات مراقب معين مع إحصائيات',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'auditorId',
                description: 'معرف المراقب',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة الزيارة',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved', 'rejected']
                )
            ),
            new OA\Parameter(
                name: 'from_date',
                description: 'من تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                description: 'إلى تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'زيارات المراقب',
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
                                    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10'),
                                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                    new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                    new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'category', type: 'string', example: 'B'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-05T10:30:00Z'),
                                    new OA\Property(
                                        property: 'branch',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                            new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'checklist',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                            new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'auditor',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966501234567'),
                                new OA\Property(property: 'role', type: 'string', example: 'مراقب'),
                            ]
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_visits', type: 'integer', example: 25),
                                new OA\Property(property: 'completed_visits', type: 'integer', example: 15),
                                new OA\Property(property: 'in_progress_visits', type: 'integer', example: 3),
                                new OA\Property(property: 'scheduled_visits', type: 'integer', example: 5),
                                new OA\Property(property: 'cancelled_visits', type: 'integer', example: 2),
                                new OA\Property(property: 'average_score', type: 'number', format: 'float', example: 82.5),
                                new OA\Property(property: 'average_percentage', type: 'number', format: 'float', example: 82.5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المراقب غير موجود',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'المراقب غير موجود'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function auditorVisits($auditorId, Request $request)
    {
        $auditor = User::findOrFail($auditorId);
        
        $query = Visit::where('auditor_id', $auditorId);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('from_date')) {
            $query->where('scheduled_date', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->where('scheduled_date', '<=', $request->input('to_date'));
        }
        
        $visits = $query->with(['branch', 'checklist'])->paginate(15);
        
        return response()->json([
            'data' => VisitResource::collection($visits),
            'auditor' => [
                'id' => $auditor->id,
                'name' => $auditor->name,
                'email' => $auditor->email,
                'phone' => $auditor->phone,
                'role' => $auditor->role,
            ],
            'statistics' => [
                'total_visits' => $visits->total(),
                'completed_visits' => $query->clone()->where('status', 'completed')->count(),
                'in_progress_visits' => $query->clone()->where('status', 'in_progress')->count(),
                'scheduled_visits' => $query->clone()->where('status', 'scheduled')->count(),
                'cancelled_visits' => $query->clone()->where('status', 'cancelled')->count(),
                'average_score' => $query->clone()->whereNotNull('total_score')->avg('total_score') ?? 0,
                'average_percentage' => $query->clone()->whereNotNull('percentage')->avg('percentage') ?? 0,
            ],
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/visits/branch/{branchId}',
        operationId: 'getBranchVisits',
        summary: 'عرض زيارات فرع',
        description: 'استرجاع جميع زيارات فرع معين مع إحصائيات',
        tags: ['Visits'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branchId',
                description: 'معرف الفرع',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة الزيارة',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved', 'rejected']
                )
            ),
            new OA\Parameter(
                name: 'from_date',
                description: 'من تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                description: 'إلى تاريخ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'زيارات الفرع',
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
                                    new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                    new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', example: '2026-02-10'),
                                    new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                    new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                    new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                    new OA\Property(property: 'category', type: 'string', example: 'B'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-05T10:30:00Z'),
                                    new OA\Property(
                                        property: 'auditor',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 2),
                                            new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                            new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'checklist',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'قائمة مراجعة النظافة'),
                                            new OA\Property(property: 'code', type: 'string', example: 'CL-001'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'branch',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                new OA\Property(property: 'code', type: 'string', example: 'BR-001'),
                                new OA\Property(property: 'address', type: 'string', example: 'شارع الملك فهد، الرياض'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966112345678'),
                                new OA\Property(property: 'email', type: 'string', example: 'main@branch.com'),
                                new OA\Property(property: 'manager_name', type: 'string', example: 'محمد أحمد'),
                            ]
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_visits', type: 'integer', example: 42),
                                new OA\Property(property: 'average_score', type: 'number', format: 'float', example: 78.3),
                                new OA\Property(property: 'average_percentage', type: 'number', format: 'float', example: 78.3),
                                new OA\Property(
                                    property: 'category_distribution',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'A', type: 'integer', example: 8),
                                        new OA\Property(property: 'B', type: 'integer', example: 15),
                                        new OA\Property(property: 'C', type: 'integer', example: 12),
                                        new OA\Property(property: 'D', type: 'integer', example: 5),
                                        new OA\Property(property: 'F', type: 'integer', example: 2),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'status_distribution',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'completed', type: 'integer', example: 25),
                                        new OA\Property(property: 'in_progress', type: 'integer', example: 5),
                                        new OA\Property(property: 'scheduled', type: 'integer', example: 8),
                                        new OA\Property(property: 'approved', type: 'integer', example: 20),
                                        new OA\Property(property: 'rejected', type: 'integer', example: 3),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الفرع غير موجود',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الفرع غير موجود'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function branchVisits($branchId, Request $request)
    {
        $branch = Branch::findOrFail($branchId);
        
        $query = Visit::where('branch_id', $branchId);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('from_date')) {
            $query->where('scheduled_date', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->where('scheduled_date', '<=', $request->input('to_date'));
        }
        
        $visits = $query->with(['auditor', 'checklist'])->paginate(15);
        
        return response()->json([
            'data' => VisitResource::collection($visits),
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'manager_name' => $branch->manager_name,
            ],
            'statistics' => [
                'total_visits' => $visits->total(),
                'average_score' => $query->clone()->whereNotNull('total_score')->avg('total_score') ?? 0,
                'average_percentage' => $query->clone()->whereNotNull('percentage')->avg('percentage') ?? 0,
                'category_distribution' => $query->clone()->whereNotNull('category')->groupBy('category')->selectRaw('category, count(*) as count')->pluck('count', 'category'),
                'status_distribution' => $query->clone()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            ],
        ]);
    }
    
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'scheduled' => '#3498db', // أزرق
            'in_progress' => '#f39c12', // برتقالي
            'completed' => '#27ae60', // أخضر
            'cancelled' => '#95a5a6', // رمادي
            'under_review' => '#9b59b6', // بنفسجي
            'approved' => '#2ecc71', // أخضر فاتح
            'rejected' => '#e74c3c', // أحمر
            default => '#bdc3c7', // رمادي فاتح
        };
    }
}