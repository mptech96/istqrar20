<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreTicketRequest;
use App\Http\Requests\V1\UpdateTicketRequest;
use App\Http\Requests\V1\StoreTicketReplyRequest;
use App\Http\Resources\V1\TicketResource;
use App\Http\Resources\V1\TicketReplyResource;
use App\Models\V1\Ticket;
use App\Models\V1\TicketReply;
use App\Models\V1\User;
use App\Models\V1\Company;
use App\Models\V1\Branch;
use App\Models\V1\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class TicketController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tickets',
        operationId: 'getTickets',
        summary: 'عرض قائمة التذاكر',
        description: 'استرجاع قائمة التذاكر مع إمكانية الفلترة والترتيب',
        tags: ['Tickets'],
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
                description: 'بحث برقم التذكرة أو العنوان أو الوصف',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة التذكرة',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['open', 'in_progress', 'pending_customer', 'resolved', 'closed', 'escalated']
                )
            ),
            new OA\Parameter(
                name: 'priority',
                description: 'فلتر بالأولوية',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['low', 'medium', 'high', 'critical']
                )
            ),
            new OA\Parameter(
                name: 'ticket_type',
                description: 'فلتر بنوع التذكرة',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['dispute', 'complaint', 'request', 'inquiry', 'technical', 'feedback']
                )
            ),
            new OA\Parameter(
                name: 'department',
                description: 'فلتر بالقسم',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['support', 'technical', 'quality', 'billing', 'admin']
                )
            ),
            new OA\Parameter(
                name: 'assigned_to',
                description: 'فلتر بالمستخدم المسند إليه',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'company_id',
                description: 'فلتر بمعرف الشركة',
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
            new OA\Parameter(
                name: 'created_by',
                description: 'فلتر بمعرف المنشئ',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'overdue_only',
                description: 'عرض التذاكر المتأخرة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'unassigned_only',
                description: 'عرض التذاكر غير المسندة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'from_date',
                description: 'من تاريخ الإنشاء',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to_date',
                description: 'إلى تاريخ الإنشاء',
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
                    enum: ['ticket_number', 'subject', 'priority', 'status', 'created_at', 'sla_due_date']
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
                schema: new OA\Schema(type: 'string', example: 'creator,company,branch,assignedTo,replies,attachments')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة التذاكر',
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
                                    new OA\Property(property: 'ticket_number', type: 'string', example: 'TKT-202602-0001'),
                                    new OA\Property(property: 'subject', type: 'string', example: 'مشكلة في التطبيق'),
                                    new OA\Property(property: 'status', type: 'string', example: 'open'),
                                    new OA\Property(property: 'priority', type: 'string', example: 'high'),
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
        $query = Ticket::query();
        
        // Filtering
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->search($search);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        
        if ($request->has('ticket_type')) {
            $query->where('ticket_type', $request->input('ticket_type'));
        }
        
        if ($request->has('department')) {
            $query->where('department', $request->input('department'));
        }
        
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }
        
        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        
        if ($request->has('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }
        
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }
        
        if ($request->boolean('unassigned_only')) {
            $query->unassigned();
        }
        
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
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
                if (in_array(trim($include), ['creator', 'company', 'branch', 'visit', 'assignedTo', 'resolver', 'replies', 'attachments'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $tickets = $query->with($with)->paginate($request->input('per_page', setting('items_per_page', '15', 'system')));
        
        return TicketResource::collection($tickets);
    }

    #[OA\Get(
        path: '/api/v1/tickets/create',
        operationId: 'getTicketCreateData',
        summary: 'بيانات إنشاء تذكرة',
        description: 'عرض الشركات والفروع والزيارات والمستخدمين',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'companies',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'شركة 1'),
                                ]
                            )
                        ),
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
                            property: 'visits',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'visit_date', type: 'string', format: 'date'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'users',
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
                            property: 'ticket_types',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                        new OA\Property(
                            property: 'priorities',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                        new OA\Property(
                            property: 'departments',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function create()
    {
        return response()->json([
            'companies' => Company::select(['id', 'name'])->get(),
            'branches' => Branch::select(['id', 'name'])->get(),
            'visits' => Visit::select(['id', 'visit_date'])->get(),
            'users' => User::select(['id', 'name'])->get(),
            'ticket_types' => ['dispute', 'complaint', 'request', 'inquiry', 'technical', 'feedback'],
            'priorities' => ['low', 'medium', 'high', 'critical'],
            'departments' => ['support', 'technical', 'quality', 'billing', 'admin'],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets',
        operationId: 'createTicket',
        summary: 'إنشاء تذكرة جديدة',
        description: 'إنشاء تذكرة جديدة مع إمكانية رفع مرفقات',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['user_type', 'subject', 'description', 'ticket_type'],
                    properties: [
                        new OA\Property(property: 'user_type', type: 'string', enum: ['company', 'branch', 'auditor', 'admin', 'quality']),
                        new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1, nullable: true),
                        new OA\Property(property: 'visit_id', type: 'integer', example: 1, nullable: true),
                        new OA\Property(property: 'subject', type: 'string', example: 'مشكلة في التطبيق'),
                        new OA\Property(property: 'description', type: 'string', example: 'شرح المشكلة بالتفصيل'),
                        new OA\Property(property: 'ticket_type', type: 'string', enum: ['dispute', 'complaint', 'request', 'inquiry', 'technical', 'feedback']),
                        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical'], default: 'medium'),
                        new OA\Property(property: 'department', type: 'string', enum: ['support', 'technical', 'quality', 'billing', 'admin'], default: 'support'),
                        new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'ticket_number', type: 'string', example: 'TKT-202602-0001'),
                                new OA\Property(property: 'subject', type: 'string', example: 'مشكلة في التطبيق'),
                                new OA\Property(property: 'status', type: 'string', example: 'open'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء التذكرة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function store(StoreTicketRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create ticket
            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateTicketNumber(),
                'created_by' => Auth::id(),
                'user_type' => $request->input('user_type'),
                'company_id' => $request->input('company_id'),
                'branch_id' => $request->input('branch_id'),
                'visit_id' => $request->input('visit_id'),
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'ticket_type' => $request->input('ticket_type'),
                'priority' => $request->input('priority', 'medium'),
                'department' => $request->input('department', 'support'),
                'status' => 'open',
            ]);
            
            // Set SLA due date based on priority
            $slaHours = match ($ticket->priority) {
                'critical' => 4,
                'high' => 8,
                'medium' => 24,
                'low' => 48,
                default => 24,
            };
            $ticket->setSlaDueDate($slaHours);
            
            // Handle attachments
            foreach ($request->file('attachments') ?? [] as $file) {
                saveAttachment($file, $ticket);
            }
            
            return response()->json([
                'data' => new TicketResource($ticket->load(['creator', 'company', 'branch'])),
                'message' => 'تم إنشاء التذكرة بنجاح'
            ], 201);
        });
    }

    #[OA\Get(
        path: '/api/v1/tickets/{ticket}',
        operationId: 'getTicket',
        summary: 'عرض تفاصيل تذكرة',
        description: 'استرجاع تفاصيل تذكرة معينة مع ردودها',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة أو رقم التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '1')
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'creator,company,branch,assignedTo,replies,attachments')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل التذكرة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'ticket_number', type: 'string', example: 'TKT-202602-0001'),
                                new OA\Property(property: 'subject', type: 'string', example: 'مشكلة في التطبيق'),
                                new OA\Property(property: 'description', type: 'string', example: 'شرح المشكلة'),
                                new OA\Property(property: 'status', type: 'string', example: 'open'),
                                new OA\Property(property: 'priority', type: 'string', example: 'high'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function show($ticket, Request $request)
    {
        $query = Ticket::where('id', $ticket)
                      ->orWhere('ticket_number', $ticket);
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['creator', 'company', 'branch', 'visit', 'assignedTo', 'resolver', 'replies', 'attachments'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $ticket = $query->with($with)->firstOrFail();
        
        return response()->json([
            'data' => new TicketResource($ticket)
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tickets/{ticket}',
        operationId: 'updateTicket',
        summary: 'تحديث بيانات تذكرة',
        description: 'تحديث بيانات تذكرة موجودة',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
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
                    new OA\Property(property: 'subject', type: 'string', example: 'عنوان محدث'),
                    new OA\Property(property: 'description', type: 'string', example: 'وصف محدث'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical']),
                    new OA\Property(property: 'department', type: 'string', enum: ['support', 'technical', 'quality', 'billing', 'admin']),
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2, nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'pending_customer', 'resolved', 'closed', 'escalated']),
                    new OA\Property(property: 'resolution', type: 'string', example: 'تم حل المشكلة'),
                    new OA\Property(property: 'customer_satisfaction', type: 'integer', minimum: 1, maximum: 5, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث التذكرة بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'subject', type: 'string', example: 'عنوان محدث'),
                                new OA\Property(property: 'status', type: 'string', example: 'in_progress'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        // صاحب التذكرة يعدّل تذكرته فقط، ومدير النظام يعدّل أي تذكرة
        $user = auth()->user();
        if ($user->role !== 'super_admin' && (int) $ticket->created_by !== (int) $user->id) {
            return response()->json(['message' => 'ليس لديك صلاحية لتعديل هذه التذكرة'], 403);
        }
        $isAdmin = $user->role === 'super_admin';
        $ticket->update($request->only($isAdmin ? [
            'subject', 'description', 'priority', 'department', 
            'assigned_to', 'status', 'resolution', 'customer_satisfaction'
        ] : ['subject', 'description', 'priority', 'customer_satisfaction']));
        
        // Handle assignment
        if ($isAdmin && $request->has('assigned_to') && $request->input('assigned_to')) {
            $ticket->assignTo($request->input('assigned_to'));
        }
        
        // Handle resolution
        if ($isAdmin && $request->has('status') && $request->input('status') === 'resolved') {
            if ($request->has('resolution')) {
                $ticket->resolve($request->input('resolution'), Auth::id());
            }
        }
        
        return response()->json([
            'data' => new TicketResource($ticket->load(['creator', 'assignedTo', 'resolver'])),
            'message' => 'تم تحديث التذكرة بنجاح'
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tickets/{ticket}',
        operationId: 'deleteTicket',
        summary: 'حذف تذكرة',
        description: 'حذف تذكرة (حذف ناعم)',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'ticket_number', type: 'string', example: 'TKT-202602-0001'),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'تم حذف التذكرة بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/restore',
        operationId: 'restoreTicket',
        summary: 'استعادة تذكرة محذوفة',
        description: 'استعادة تذكرة تم حذفها (حذف ناعم)',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'ticket_number', type: 'string', example: 'TKT-202602-0001'),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function restore($id)
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $ticket->restore();
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'تم استعادة التذكرة بنجاح'
        ]);
    }

    // =========================================================================
    // Ticket Replies
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tickets/{ticket}/replies',
        operationId: 'getTicketReplies',
        summary: 'عرض ردود التذكرة',
        description: 'استرجاع قائمة ردود تذكرة معينة',
        tags: ['Ticket Replies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include_internal',
                description: 'تضمين الردود الداخلية',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'replier,attachments')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة ردود التذكرة',
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
                                    new OA\Property(property: 'message', type: 'string', example: 'نحن نعمل على حل المشكلة'),
                                    new OA\Property(property: 'is_internal', type: 'boolean', example: false),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(
                                        property: 'replier',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 2),
                                            new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                        ]
                                    ),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function replies(Ticket $ticket, Request $request)
    {
        $query = $ticket->replies();
        
        if (!$request->boolean('include_internal') && !auth()->user()?->role == 'super_admin') {
            $query->public();
        }
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['replier', 'attachments'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $replies = $query->with($with)
                        ->orderBy('created_at', 'asc')
                        ->paginate($request->input('per_page', setting('items_per_page', '15', 'system')));
        
        return TicketReplyResource::collection($replies);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/replies',
        operationId: 'createTicketReply',
        summary: 'إضافة رد على تذكرة',
        description: 'إضافة رد جديد على تذكرة معينة',
        tags: ['Ticket Replies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'نحن نعمل على حل المشكلة'),
                        new OA\Property(property: 'is_internal', type: 'boolean', example: false, default: false),
                        new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إضافة الرد بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'message', type: 'string', example: 'نحن نعمل على حل المشكلة'),
                                new OA\Property(property: 'is_internal', type: 'boolean', example: false),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إضافة الرد بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function addReply(StoreTicketReplyRequest $request, Ticket $ticket)
    {
        return DB::transaction(function () use ($request, $ticket) {
            // Create reply
            $reply = $ticket->replies()->create([
                'replied_by' => Auth::id(),
                'message' => $request->input('message'),
                'is_internal' => $request->boolean('is_internal', false),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Handle attachments
            foreach ($request->file('attachments') ?? [] as $file) {
                saveAttachment($file, $reply);
            }
            
            // Update attachments status
            if ($request->hasFile('attachments')) {
                $reply->updateAttachmentsStatus();
            }
            
            // Update ticket status if it's open and not internal
            if (!$reply->is_internal && $ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }
            
            return response()->json([
                'data' => new TicketReplyResource($reply->load(['replier', 'attachments'])),
                'message' => 'تم إضافة الرد بنجاح'
            ], 201);
        });
    }

    #[OA\Put(
        path: '/api/v1/tickets/{ticket}/replies/{reply}',
        operationId: 'updateTicketReply',
        summary: 'تحديث رد على تذكرة',
        description: 'تحديث رد موجود',
        tags: ['Ticket Replies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'reply',
                description: 'معرف الرد',
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
                    new OA\Property(property: 'message', type: 'string', example: 'نحن نعمل على حل المشكلة - محدث'),
                    new OA\Property(property: 'is_internal', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الرد بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الرد بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'message', type: 'string', example: 'نحن نعمل على حل المشكلة - محدث'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الرد غير موجود'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function updateReply(Request $request, Ticket $ticket, TicketReply $reply)
    {
        if ($reply->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'الرد لا ينتمي إلى هذه التذكرة'
            ], 404);
        }
        
        $request->validate([
            'message' => ['sometimes', 'string'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);
        
        $reply->update($request->only(['message', 'is_internal']));
        
        return response()->json([
            'data' => new TicketReplyResource($reply->load(['replier', 'attachments'])),
            'message' => 'تم تحديث الرد بنجاح'
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tickets/{ticket}/replies/{reply}',
        operationId: 'deleteTicketReply',
        summary: 'حذف رد على تذكرة',
        description: 'حذف رد معين (حذف ناعم)',
        tags: ['Ticket Replies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'reply',
                description: 'معرف الرد',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف الرد بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف الرد بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الرد غير موجود'
            ),
        ]
    )]
    public function destroyReply(Ticket $ticket, TicketReply $reply)
    {
        if ($reply->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'الرد لا ينتمي إلى هذه التذكرة'
            ], 404);
        }
        
        $reply->delete();
        
        return response()->json([
            'data' => new TicketReplyResource($reply),
            'message' => 'تم حذف الرد بنجاح'
        ]);
    }

    // =========================================================================
    // Additional Actions
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/assign',
        operationId: 'assignTicket',
        summary: 'إسناد تذكرة إلى مستخدم',
        description: 'إسناد تذكرة إلى مستخدم معين',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['assigned_to'],
                properties: [
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إسناد التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم إسناد التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                                new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);
        
        $ticket->assignTo($request->input('assigned_to'));
        
        return response()->json([
            'data' => new TicketResource($ticket->load('assignedTo')),
            'message' => 'تم إسناد التذكرة بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/resolve',
        operationId: 'resolveTicket',
        summary: 'حل تذكرة',
        description: 'تحديد حل للتذكرة وتغيير حالتها إلى محلولة',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['resolution'],
                properties: [
                    new OA\Property(property: 'resolution', type: 'string', example: 'تم حل المشكلة'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حل التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حل التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'resolved'),
                                new OA\Property(property: 'resolution', type: 'string', example: 'تم حل المشكلة'),
                                new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function resolve(Request $request, Ticket $ticket)
    {
        $request->validate([
            'resolution' => ['required', 'string'],
        ]);
        
        $ticket->resolve($request->input('resolution'));
        
        return response()->json([
            'data' => new TicketResource($ticket->load('resolver')),
            'message' => 'تم حل التذكرة بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/close',
        operationId: 'closeTicket',
        summary: 'إغلاق تذكرة',
        description: 'إغلاق تذكرة محلولة',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إغلاق التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم إغلاق التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'closed'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن إغلاق التذكرة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إغلاق التذكرة إلا إذا كانت محلولة'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function close(Ticket $ticket)
    {
        if ($ticket->status !== 'resolved') {
            return response()->json([
                'message' => 'لا يمكن إغلاق التذكرة إلا إذا كانت محلولة'
            ], 400);
        }
        
        $ticket->close();
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'تم إغلاق التذكرة بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/reopen',
        operationId: 'reopenTicket',
        summary: 'إعادة فتح تذكرة',
        description: 'إعادة فتح تذكرة مغلقة',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إعادة فتح التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم إعادة فتح التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'open'),
                                new OA\Property(property: 'resolution', type: 'string', example: null),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن إعادة فتح التذكرة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن إعادة فتح التذكرة إلا إذا كانت مغلقة'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function reopen(Ticket $ticket)
    {
        if ($ticket->status !== 'closed') {
            return response()->json([
                'message' => 'لا يمكن إعادة فتح التذكرة إلا إذا كانت مغلقة'
            ], 400);
        }
        
        $ticket->reopen();
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'تم إعادة فتح التذكرة بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tickets/{ticket}/escalate',
        operationId: 'escalateTicket',
        summary: 'رفع مستوى التذكرة',
        description: 'رفع مستوى التذكرة إلى مستوى أعلى',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'معرف التذكرة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم رفع مستوى التذكرة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم رفع مستوى التذكرة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'escalated'),
                                new OA\Property(property: 'priority', type: 'string', example: 'critical'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'التذكرة غير موجودة'
            ),
        ]
    )]
    public function escalate(Ticket $ticket)
    {
        $ticket->escalate();
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'تم رفع مستوى التذكرة بنجاح'
        ]);
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tickets/statistics/overview',
        operationId: 'getTicketsStatisticsOverview',
        summary: 'إحصائيات عامة للتذاكر',
        description: 'استرجاع إحصائيات عامة عن التذاكر',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصائيات التذاكر',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'total_tickets', type: 'integer', example: 150),
                        new OA\Property(property: 'open_tickets', type: 'integer', example: 25),
                        new OA\Property(property: 'in_progress_tickets', type: 'integer', example: 30),
                        new OA\Property(property: 'resolved_tickets', type: 'integer', example: 60),
                        new OA\Property(property: 'closed_tickets', type: 'integer', example: 35),
                        new OA\Property(property: 'overdue_tickets', type: 'integer', example: 5),
                        new OA\Property(property: 'unassigned_tickets', type: 'integer', example: 8),
                        new OA\Property(property: 'avg_response_time', type: 'number', format: 'float', example: 3.5),
                        new OA\Property(property: 'avg_resolution_time', type: 'number', format: 'float', example: 24.2),
                        new OA\Property(property: 'avg_satisfaction', type: 'number', format: 'float', example: 4.2),
                        new OA\Property(
                            property: 'by_priority',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'critical', type: 'integer', example: 5),
                                new OA\Property(property: 'high', type: 'integer', example: 15),
                                new OA\Property(property: 'medium', type: 'integer', example: 80),
                                new OA\Property(property: 'low', type: 'integer', example: 50),
                            ]
                        ),
                        new OA\Property(
                            property: 'by_type',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'integer')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function statisticsOverview(Request $request)
    {
        $query = Ticket::query();
        
        // Apply date filters if provided
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }
        
        $total = $query->count();
        
        $byStatus = [
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'pending_customer' => (clone $query)->where('status', 'pending_customer')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'closed' => (clone $query)->where('status', 'closed')->count(),
            'escalated' => (clone $query)->where('status', 'escalated')->count(),
        ];
        
        $byPriority = [
            'critical' => (clone $query)->where('priority', 'critical')->count(),
            'high' => (clone $query)->where('priority', 'high')->count(),
            'medium' => (clone $query)->where('priority', 'medium')->count(),
            'low' => (clone $query)->where('priority', 'low')->count(),
        ];
        
        $byType = (clone $query)
            ->select('ticket_type', DB::raw('count(*) as total'))
            ->groupBy('ticket_type')
            ->pluck('total', 'ticket_type');
        
        $overdue = (clone $query)->overdue()->count();
        $unassigned = (clone $query)->unassigned()->count();
        
        // Average times
        $avgResponseTime = TicketReply::join('tickets', 'tickets.id', '=', 'ticket_replies.ticket_id')
            ->whereNotNull('tickets.first_response_at')
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.first_response_at)'));
        
        $avgResolutionTime = Ticket::whereNotNull('resolved_at')
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, resolved_at)'));
        
        $avgSatisfaction = Ticket::whereNotNull('customer_satisfaction')
            ->avg('customer_satisfaction');
        
        return response()->json([
            'total_tickets' => $total,
            'open_tickets' => $byStatus['open'] + $byStatus['in_progress'] + $byStatus['pending_customer'],
            'in_progress_tickets' => $byStatus['in_progress'],
            'resolved_tickets' => $byStatus['resolved'],
            'closed_tickets' => $byStatus['closed'],
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_type' => $byType,
            'overdue_tickets' => $overdue,
            'unassigned_tickets' => $unassigned,
            'avg_response_time' => round((float) $avgResponseTime, 2),
            'avg_resolution_time' => round((float) $avgResolutionTime, 2),
            'avg_satisfaction' => round((float) $avgSatisfaction, 2),
        ]);
    }
}
