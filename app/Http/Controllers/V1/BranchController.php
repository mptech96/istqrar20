<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreBranchRequest;
use App\Http\Requests\V1\UpdateBranchRequest;
use App\Http\Resources\V1\BranchResource;
use App\Http\Resources\V1\StatisticResource;
use App\Models\V1\Branch;
use App\Models\V1\Company;
use App\Models\V1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class BranchController extends Controller
{
    #[OA\Get(
        path: '/api/v1/branches',
        operationId: 'getBranches',
        summary: 'عرض قائمة الفروع',
        description: 'استرجاع قائمة الفروع مع إمكانية الفلترة والترتيب',
        tags: ['Branches'],
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
                description: 'بحث بالاسم أو البريد الإلكتروني أو الهاتف',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'company_id',
                description: 'فلتر بمعرف الشركة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة الفرع',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'city',
                description: 'فلتر بالمدينة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category',
                description: 'فلتر بالتصنيف',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'needs_visit',
                description: 'فلتر بالفروع التي تحتاج زيارة',
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
                    default: 'created_at',
                    enum: ['name', 'email', 'created_at', 'compliance_score', 'next_scheduled_visit']
                )
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'اتجاه الترتيب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة الفروع',
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
                                    new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                    new OA\Property(property: 'email', type: 'string', example: 'branch@company.com'),
                                    new OA\Property(property: 'phone', type: 'string', example: '+966500000001'),
                                    new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                                    new OA\Property(property: 'status', type: 'boolean', example: true),
                                    new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 85.5),
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
        $query = Branch::query();
        
        // Filtering
        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }
        
        if ($request->has('city')) {
            $query->where('city', $request->input('city'));
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }
        
        // Filter branches needing visit
        if ($request->boolean('needs_visit')) {
            $query->where(function ($q) {
                $q->whereNull('next_scheduled_visit')
                  ->orWhere('next_scheduled_visit', '<=', now());
            });
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $branches = $query->with(['company', 'manager'])->paginate($request->input('per_page', 15));
        
        return BranchResource::collection($branches);
    }
    
    public function create()
    {
        return Company::pluck('id', 'name');
    }
    
    #[OA\Post(
        path: '/api/v1/branches',
        operationId: 'createBranch',
        summary: 'إنشاء فرع جديد مع مديره',
        description: 'إنشاء فرع جديد مع بيانات مدير الفرع المرتبط به',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: [
                        'company_id', 'name', 'email', 'phone', 'city', 'address', 'category',
                        'user_name', 'user_email', 'user_password', 'user_password_confirmation', 'user_phone'
                    ],
                    properties: [
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'branch.riyadh@company.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+966500000002'),
                        new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                        new OA\Property(property: 'address', type: 'string', example: 'حي النخيل، برج التجارة'),
                        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 24.7136),
                        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 46.6753),
                        new OA\Property(property: 'category', type: 'string', example: 'رئيسي'),
                        new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 0.00),
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        
                        // Branch Manager User data
                        new OA\Property(property: 'user_name', type: 'string', example: 'مدير الفرع'),
                        new OA\Property(property: 'user_email', type: 'string', format: 'email', example: 'branch.manager@company.com'),
                        new OA\Property(property: 'user_password', type: 'string', format: 'password'),
                        new OA\Property(property: 'user_password_confirmation', type: 'string', format: 'password'),
                        new OA\Property(property: 'user_phone', type: 'string', example: '+966511111112'),
                        new OA\Property(property: 'user_personal_photo', type: 'file', format: 'binary'),
                        new OA\Property(property: 'user_birth_date', type: 'string', format: 'date', example: '1992-05-15'),
                        new OA\Property(property: 'user_gender', type: 'integer', enum: [1, 2, 3], example: 2),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء الفرع بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'phone', type: 'string'),
                                new OA\Property(property: 'city', type: 'string'),
                                new OA\Property(property: 'status', type: 'boolean'),
                                new OA\Property(property: 'manager_name', type: 'string'),
                                new OA\Property(property: 'manager_email', type: 'string'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء الفرع بنجاح'),
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
    public function store(StoreBranchRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Verify company exists
            $company = Company::findOrFail($request->input('company_id'));
            
            // Handle manager personal_photo upload
            $personal_photoPath = null;
            if ($request->hasFile('user_personal_photo')) {
                $personal_photoPath = __saveFile($request->file('user_personal_photo'), base_path('public/uploads/users/personal_photos/'));
            }
            
            // Create branch manager user
            $manager = User::create([
                'name' => $request->input('user_name'),
                'email' => $request->input('user_email'),
                'password' => Hash::make($request->input('user_password')),
                'phone' => $request->input('user_phone'),
                'personal_photo' => $personal_photoPath,
                'birth_date' => $request->input('user_birth_date'),
                'gender' => $request->input('user_gender'),
                'company_id' => $company->id,
                'role' => 'branch_manager',
            ]);
            
            // Create branch
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'city' => $request->input('city'),
                'address' => $request->input('address'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'manager_user_id' => $manager->id,
                'manager_name' => $manager->name,
                'manager_email' => $manager->email,
                'manager_phone' => $manager->phone,
                'category' => $request->input('category'),
                'compliance_score' => $request->input('compliance_score', 0.00),
                'status' => true, // Always active when created
            ]);
            
            // Update user with branch_id
            $manager->update(['branch_id' => $branch->id]);
            
            // Update company total branches count
            $company->increment('total_branches');
            
            return response()->json([
                'data' => new BranchResource($branch->load(['company', 'manager'])),
                'message' => 'تم إنشاء الفرع بنجاح'
            ], 201);
        });
    }
    
    #[OA\Get(
        path: '/api/v1/branches/{branch}',
        operationId: 'getBranch',
        summary: 'عرض تفاصيل فرع',
        description: 'استرجاع تفاصيل فرع معين',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branch',
                description: 'معرف الفرع',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'company,manager,users,visits')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل الفرع',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض'),
                                new OA\Property(property: 'email', type: 'string', example: 'branch.riyadh@company.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000002'),
                                new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                                new OA\Property(property: 'address', type: 'string', example: 'حي النخيل، برج التجارة'),
                                new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 24.7136),
                                new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 46.6753),
                                new OA\Property(property: 'manager_name', type: 'string', example: 'مدير الفرع'),
                                new OA\Property(property: 'manager_email', type: 'string', example: 'branch.manager@company.com'),
                                new OA\Property(property: 'manager_phone', type: 'string', example: '+966511111112'),
                                new OA\Property(property: 'category', type: 'string', example: 'رئيسي'),
                                new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 85.5),
                                new OA\Property(property: 'total_visits', type: 'integer', example: 10),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الفرع غير موجود'
            ),
        ]
    )]
    public function show(Branch $branch)
    {
        $branch->load(['company', 'manager', 'users', 'visits']);
        return response()->json([
            'data' => new BranchResource($branch)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/branches/{branch}',
        operationId: 'updateBranch',
        summary: 'تحديث بيانات فرع',
        description: 'تحديث بيانات فرع موجود',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branch',
                description: 'معرف الفرع',
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
                    new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض (محدث)'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'updated.branch@company.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '+966500000003'),
                    new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                    new OA\Property(property: 'address', type: 'string', example: 'حي العليا، برج المملكة'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 24.7539),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 46.6424),
                    new OA\Property(property: 'category', type: 'string', example: 'رئيسي'),
                    new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 95.5),
                    new OA\Property(property: 'total_visits', type: 'integer', example: 12),
                    new OA\Property(property: 'last_visit_date', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'next_scheduled_visit', type: 'string', format: 'date', example: '2024-02-15'),
                    new OA\Property(property: 'status', type: 'boolean', example: true),
                    new OA\Property(property: 'manager_name', type: 'string', example: 'مدير الفرع (محدث)'),
                    new OA\Property(property: 'manager_email', type: 'string', format: 'email', example: 'updated.manager@company.com'),
                    new OA\Property(property: 'manager_phone', type: 'string', example: '+966511111113'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الفرع بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث الفرع بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض (محدث)'),
                                new OA\Property(property: 'email', type: 'string', example: 'updated.branch@company.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000003'),
                                new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                                new OA\Property(property: 'address', type: 'string', example: 'حي العليا، برج المملكة'),
                                new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 95.5),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الفرع غير موجود'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        // Update branch data
        $branch->update($request->only([
            'name', 'email', 'phone', 'city', 'address',
            'latitude', 'longitude', 'category', 'compliance_score',
            'total_visits', 'last_visit_date', 'next_scheduled_visit',
            'manager_name', 'manager_email', 'manager_phone'
        ]));
        
        // Handle status update
        if ($request->has('status')) {
            $branch->status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $branch->save();
        }
        
        // Update manager user if manager info changed
        if ($branch->manager_user_id && 
            ($request->has('manager_email') || $request->has('manager_name') || $request->has('manager_phone'))) {
            
            $manager = User::find($branch->manager_user_id);
            if ($manager) {
                $manager->update([
                    'name' => $request->input('manager_name', $manager->name),
                    'email' => $request->input('manager_email', $manager->email),
                    'phone' => $request->input('manager_phone', $manager->phone),
                ]);
            }
        }
        
        return response()->json([
            'data' => new BranchResource($branch->load(['company', 'manager'])),
            'message' => 'تم تحديث الفرع بنجاح'
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/branches/{branch}',
        operationId: 'deleteBranch',
        summary: 'حذف فرع',
        description: 'حذف فرع معين (حذف ناعم)',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branch',
                description: 'معرف الفرع',
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
                description: 'تم حذف الفرع بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف الفرع بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض'),
                                new OA\Property(property: 'email', type: 'string', example: 'branch.riyadh@company.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000002'),
                                new OA\Property(property: 'status', type: 'boolean', example: false),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الفرع غير موجود'
            ),
        ]
    )]
    public function destroy(Branch $branch)
    {
        DB::transaction(function () use ($branch) {
            // Decrement company total branches count
            $branch->company()->decrement('total_branches');
            
            // Remove manager association
            if ($branch->manager_user_id) {
                $branch->update([
                    'manager_user_id' => null,
                    'manager_name' => null,
                    'manager_email' => null,
                    'manager_phone' => null,
                ]);
            }
            
            $branch->delete();
        });
        
        return response()->json([
            'data' => new BranchResource($branch),
            'message' => 'تم حذف الفرع بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/branches/{branch}/restore',
        operationId: 'restoreBranch',
        summary: 'استعادة فرع محذوف',
        description: 'استعادة فرع تم حذفه (حذف ناعم)',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branch',
                description: 'معرف الفرع',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة الفرع بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة الفرع بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي - الرياض'),
                                new OA\Property(property: 'email', type: 'string', example: 'branch.riyadh@company.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000002'),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الفرع غير موجود'
            ),
        ]
    )]
    public function restore($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);
        $branch->restore();
        
        // Increment company total branches count
        $branch->company()->increment('total_branches');
        
        return response()->json([
            'data' => new BranchResource($branch),
            'message' => 'تم استعادة الفرع بنجاح'
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/branches/{branch}/statistics',
        operationId: 'getBranchStatistics',
        summary: 'عرض إحصايات فرع',
        description: 'استرجاع تفاصيل إحصايات فرع معين',
        tags: ['Branches'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'branch',
                description: 'معرف فرع',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصايات الفرع',
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
                                    new OA\Property(property: 'record_date', type: 'string', format: 'date-time', example: '2026-02-05 02:00:00 AM'),
                                    new OA\Property(property: 'metric_name', type: 'string', example: 'total_visits'),
                                    new OA\Property(property: 'metric_value', type: 'float', example: 10.00),
                                    new OA\Property(property: 'period_type', type: 'string', example: 'daily'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function statistics(Branch $branch)
    {
        return StatisticResource::collection($branch->statistics);
    }
    
}
