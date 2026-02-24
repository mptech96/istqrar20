<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\StatisticResource;
use App\Models\V1\User;
use App\Models\V1\Company;
use App\Models\V1\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/v1/users',
        operationId: 'getUsers',
        summary: 'عرض قائمة المستخدمين',
        description: 'استرجاع قائمة المستخدمين مع إمكانية الفلترة والترتيب',
        tags: ['Users'],
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
                description: 'بحث بالاسم أو البريد الإلكتروني أو الهاتف',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'role',
                description: 'فلتر بالدور',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['super_admin', 'auditor', 'company_admin', 'branch_manager']
                )
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
                name: 'status',
                description: 'فلتر بحالة المستخدم',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'ترتيب النتائج حسب',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'created_at',
                    enum: ['name', 'email', 'created_at', 'last_login_at']
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
                description: 'قائمة المستخدمين',
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
                                    new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                                    new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                    new OA\Property(property: 'phone', type: 'string', example: '+966511111111'),
                                    new OA\Property(property: 'role', type: 'string', example: 'auditor'),
                                    new OA\Property(property: 'status', type: 'integer', example: 1),
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
        $query = User::query();
        
        // Filtering
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }
        
        if ($request->has('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('national_number', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $users = $query->with(['company', 'branch', 'creator'])->paginate($request->input('per_page', 15));
        
        return UserResource::collection($users);
    }
    
    #[OA\Post(
        path: '/api/v1/users',
        operationId: 'createUser',
        summary: 'إنشاء مستخدم جديد',
        description: 'إنشاء مستخدم جديد في النظام',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['name', 'email', 'password', 'password_confirmation', 'phone', 'role'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                        new OA\Property(property: 'phone', type: 'string', example: '+966511111111'),
                        new OA\Property(
                            property: 'role',
                            type: 'string',
                            enum: ['super_admin', 'auditor', 'company_admin', 'branch_manager'],
                            example: 'auditor'
                        ),
                        //new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
                        //new OA\Property(property: 'branch_id', type: 'integer', example: 1, nullable: true),
                        new OA\Property(property: 'department', type: 'string', example: 'الجودة'),
                        new OA\Property(property: 'position', type: 'string', example: 'مراجع'),
                        new OA\Property(property: 'national_number', type: 'string', example: '1234567890'),
                        new OA\Property(property: 'municipality_certificate', type: 'file', format: 'binary'),
                        new OA\Property(property: 'driving_license', type: 'file', format: 'binary'),
                        new OA\Property(property: 'personal_photo', type: 'file', format: 'binary'),
                        new OA\Property(property: 'certificate_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'license_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'id_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], example: 'male'),
                        new OA\Property(property: 'status', type: 'integer', enum: [0, 1, 2], example: 1),
                        new OA\Property(property: 'must_change_password', type: 'boolean', example: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء المستخدم بنجاح',
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
                                new OA\Property(property: 'role', type: 'string'),
                                new OA\Property(property: 'status', type: 'integer'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء المستخدم بنجاح'),
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
    public function store(StoreUserRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Handle file uploads
            $municipalityCertificatePath = null;
            $drivingLicensePath = null;
            $personalPhotoPath = null;
            
            if ($request->hasFile('municipality_certificate')) {
                $municipalityCertificatePath = __saveFile(
                    $request->file('municipality_certificate'), 
                    base_path('public/uploads/users/documents/')
                );
            }
            
            if ($request->hasFile('driving_license')) {
                $drivingLicensePath = __saveFile(
                    $request->file('driving_license'), 
                    base_path('public/uploads/users/documents/')
                );
            }
            
            if ($request->hasFile('personal_photo')) {
                $personalPhotoPath = __saveFile(
                    $request->file('personal_photo'), 
                    base_path('public/uploads/users/personal_photos/')
                );
            }
            
            // Prepare user data
            $userData = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'phone' => $request->input('phone'),
                'role' => $request->input('role'),
                'company_id' => $request->input('company_id'),
                'branch_id' => $request->input('branch_id'),
                'department' => $request->input('department'),
                'position' => $request->input('position'),
                'birth_date' => $request->input('birth_date'),
                'gender' => $request->input('gender'),
                'status' => $request->input('status', 1),
                'must_change_password' => $request->input('must_change_password', false),
                'created_by' => Auth::id(),
            ];
            
            // Add auditor-specific fields
            if ($request->input('role') === 'auditor') {
                $userData = array_merge($userData, [
                    'national_number' => $request->input('national_number'),
                    'municipality_certificate' => $municipalityCertificatePath,
                    'driving_license' => $drivingLicensePath,
                    'personal_photo' => $personalPhotoPath,
                    'certificate_expiry' => $request->input('certificate_expiry'),
                    'license_expiry' => $request->input('license_expiry'),
                    'id_expiry' => $request->input('id_expiry'),
                    'is_certificate_valid' => $request->input('is_certificate_valid', true),
                    'is_license_valid' => $request->input('is_license_valid', true),
                    'is_id_valid' => $request->input('is_id_valid', true),
                ]);
            }
            
            // Create user
            $user = User::create($userData);
            
            // If user is branch manager, update branch manager info
            if ($user->role === 'branch_manager' && $user->branch_id) {
                $branch = Branch::find($user->branch_id);
                if ($branch) {
                    $branch->update([
                        'manager_user_id' => $user->id,
                        'manager_name' => $user->name,
                        'manager_email' => $user->email,
                        'manager_phone' => $user->phone,
                    ]);
                }
            }
            
            return response()->json([
                'data' => new UserResource($user->load(['company', 'branch', 'creator'])),
                'message' => 'تم إنشاء المستخدم بنجاح'
            ], 201);
        });
    }
    
    #[OA\Post(
        path: '/api/v1/users/un-block/{email}',
        operationId: 'unBlockUser',
        summary: 'رفع الحظر عن مستخدم',
        description: 'رفع حظر مستخدم حاول تسجيل الدخول بمحاولات',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'email',
                description: 'ايميل المستخدم',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: "user@user.com")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم رفع حظر المستخدم'
            ),
        ]
    )]
    
    public function unBlockUser(string $email)
    {
        \App\Models\V1\LoginAttempt::where('email', $email)
            ->where('successful', false)
            ->delete();
            
        return response()->json([
            'message' => 'تم رفع حظر المستخدم'
        ], 200);
    }
    
    #[OA\Get(
        path: '/api/v1/users/{user}',
        operationId: 'getUser',
        summary: 'عرض تفاصيل مستخدم',
        description: 'استرجاع تفاصيل مستخدم معين',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المستخدم',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'company,branch,creator')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل المستخدم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                                new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966511111111'),
                                new OA\Property(property: 'role', type: 'string', example: 'auditor'),
                                new OA\Property(property: 'national_number', type: 'string', example: '1234567890'),
                                new OA\Property(property: 'status', type: 'integer', example: 1),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المستخدم غير موجود'
            ),
        ]
    )]
    public function show(User $user)
    {
        $user->load(['company', 'branch', 'creator', 'managedBranch']);
        return response()->json([
            'data' => new UserResource($user)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/users/{user}',
        operationId: 'updateUser',
        summary: 'تحديث بيانات مستخدم',
        description: 'تحديث بيانات مستخدم موجود',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المستخدم',
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
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد (محدث)'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'updated.user@example.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+966511111112'),
                        new OA\Property(property: 'municipality_certificate', type: 'file', format: 'binary'),
                        new OA\Property(property: 'driving_license', type: 'file', format: 'binary'),
                        new OA\Property(property: 'personal_photo', type: 'file', format: 'binary'),
                        new OA\Property(property: 'role', type: 'string', enum: ['super_admin', 'auditor', 'company_admin', 'branch_manager'], example: 'auditor'),
                        //new OA\Property(property: 'company_id', type: 'integer', example: 1, nullable: true),
                        //new OA\Property(property: 'branch_id', type: 'integer', example: 1, nullable: true),
                        new OA\Property(property: 'department', type: 'string', example: 'الجودة'),
                        new OA\Property(property: 'position', type: 'string', example: 'مراجع'),
                        new OA\Property(property: 'national_number', type: 'string', example: '1234567890'),
                        new OA\Property(property: 'certificate_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'license_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'id_expiry', type: 'string', format: 'date', example: '2025-12-31'),
                        new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], example: 'male'),
                        new OA\Property(property: 'status', type: 'integer', enum: [0, 1, 2], example: 1),
                        new OA\Property(property: 'must_change_password', type: 'boolean', example: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث المستخدم بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث المستخدم بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد (محدث)'),
                                new OA\Property(property: 'email', type: 'string', example: 'updated.user@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966511111112'),
                                new OA\Property(property: 'role', type: 'string', example: 'auditor'),
                                new OA\Property(property: 'status', type: 'integer', example: 1),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المستخدم غير موجود'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(UpdateUserRequest $request, User $user)
    {
        return DB::transaction(function () use ($request, $user) {
            $oldRole = $user->role;
            $oldBranchId = $user->branch_id;
            
            // Handle file uploads
            if ($request->hasFile('municipality_certificate')) {
                if ($user->municipality_certificate) {
                    delete_file_req($user->municipality_certificate, base_path('public/uploads/users/documents/'));
                }
                $user->municipality_certificate = __saveFile(
                    $request->file('municipality_certificate'), 
                    base_path('public/uploads/users/documents/')
                );
            }
            
            if ($request->hasFile('driving_license')) {
                if ($user->driving_license) {
                    delete_file_req($user->driving_license, base_path('public/uploads/users/documents/'));
                }
                $user->driving_license = __saveFile(
                    $request->file('driving_license'), 
                    base_path('public/uploads/users/documents/')
                );
            }
            
            if ($request->hasFile('personal_photo')) {
                if ($user->personal_photo) {
                    delete_file_req($user->personal_photo, base_path('public/uploads/users/personal_photos/'));
                }
                $user->personal_photo = __saveFile(
                    $request->file('personal_photo'), 
                    base_path('public/uploads/users/personal_photos/')
                );
            }
            
            // Update user data
            $user->update($request->only([
                'name', 'email', 'phone', 'role', 'company_id', 'branch_id',
                'department', 'position', 'national_number', 'certificate_expiry',
                'license_expiry', 'id_expiry', 'birth_date', 'gender', 
                'is_certificate_valid', 'is_license_valid', 'is_id_valid'
            ]));
            
            // Handle status and must_change_password
            if ($request->has('status')) {
                $user->status = $request->input('status');
            }
            
            if ($request->has('must_change_password')) {
                $user->must_change_password = filter_var($request->input('must_change_password'), FILTER_VALIDATE_BOOLEAN);
            }
            
            $user->save();
            
            // Handle role change from branch_manager to other role
            if ($oldRole === 'branch_manager' && $user->role !== 'branch_manager' && $oldBranchId) {
                $branch = Branch::find($oldBranchId);
                if ($branch && $branch->manager_user_id === $user->id) {
                    $branch->update([
                        'manager_user_id' => null,
                        'manager_name' => null,
                        'manager_email' => null,
                        'manager_phone' => null,
                    ]);
                }
            }
            
            // If user is now branch manager, update branch manager info
            if ($user->role === 'branch_manager' && $user->branch_id) {
                $branch = Branch::find($user->branch_id);
                if ($branch) {
                    $branch->update([
                        'manager_user_id' => $user->id,
                        'manager_name' => $user->name,
                        'manager_email' => $user->email,
                        'manager_phone' => $user->phone,
                    ]);
                }
            }
            
            // If branch changed and user was branch manager
            if ($user->role === 'branch_manager' && $oldBranchId !== $user->branch_id) {
                // Clear old branch manager info
                if ($oldBranchId) {
                    $oldBranch = Branch::find($oldBranchId);
                    if ($oldBranch && $oldBranch->manager_user_id === $user->id) {
                        $oldBranch->update([
                            'manager_user_id' => null,
                            'manager_name' => null,
                            'manager_email' => null,
                            'manager_phone' => null,
                        ]);
                    }
                }
                
                // Set new branch manager info
                if ($user->branch_id) {
                    $newBranch = Branch::find($user->branch_id);
                    if ($newBranch) {
                        $newBranch->update([
                            'manager_user_id' => $user->id,
                            'manager_name' => $user->name,
                            'manager_email' => $user->email,
                            'manager_phone' => $user->phone,
                        ]);
                    }
                }
            }
            
            return response()->json([
                'data' => new UserResource($user->load(['company', 'branch', 'creator'])),
                'message' => 'تم تحديث المستخدم بنجاح'
            ]);
        });
    }
    
    #[OA\Delete(
        path: '/api/v1/users/{user}',
        operationId: 'deleteUser',
        summary: 'حذف مستخدم',
        description: 'حذف مستخدم معين (حذف ناعم)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المستخدم',
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
                description: 'تم حذف المستخدم بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف المستخدم بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                                new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966511111111'),
                                new OA\Property(property: 'status', type: 'integer', example: 0),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المستخدم غير موجود'
            ),
        ]
    )]
    public function destroy(User $user)
    {
        // If user is branch manager, remove from branch
        if ($user->role === 'branch_manager' && $user->branch_id) {
            $branch = Branch::find($user->branch_id);
            if ($branch) {
                $branch->update([
                    'manager_user_id' => null,
                    'manager_name' => null,
                    'manager_email' => null,
                    'manager_phone' => null,
                ]);
            }
        }
        
        $user->delete();
        
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/users/{user}/restore',
        operationId: 'restoreUser',
        summary: 'استعادة مستخدم محذوف',
        description: 'استعادة مستخدم تم حذفه (حذف ناعم)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المستخدم',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة المستخدم بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة المستخدم بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'محمد أحمد'),
                                new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966511111111'),
                                new OA\Property(property: 'status', type: 'integer', example: 1),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'المستخدم غير موجود'
            ),
        ]
    )]
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        
        return response()->json([
            'data' => new UserResource($user),
            'message' => 'تم استعادة المستخدم بنجاح'
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/users/{user}/documents-expiry',
        operationId: 'getUserDocumentsExpiry',
        summary: 'عرض حالة صلاحية وثائق المراجع',
        description: 'عرض حالة صلاحية وثائق المراجع (شهادة البلدية، رخصة القيادة، الهوية)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المستخدم (يجب أن يكون دور المراجع)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'حالة صلاحية الوثائق',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'certificate_expiry', type: 'string', format: 'date'),
                        new OA\Property(property: 'license_expiry', type: 'string', format: 'date'),
                        new OA\Property(property: 'id_expiry', type: 'string', format: 'date'),
                        new OA\Property(property: 'is_certificate_valid', type: 'boolean'),
                        new OA\Property(property: 'is_license_valid', type: 'boolean'),
                        new OA\Property(property: 'is_id_valid', type: 'boolean'),
                        new OA\Property(property: 'certificate_days_left', type: 'integer'),
                        new OA\Property(property: 'license_days_left', type: 'integer'),
                        new OA\Property(property: 'id_days_left', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'المستخدم ليس مراجعاً'
            ),
        ]
    )]
    public function documentsExpiry(User $user)
    {
        if ($user->role !== 'auditor') {
            return response()->json([
                'message' => 'المستخدم ليس مراجعاً'
            ], 400);
        }
        
        $now = now();
        
        return response()->json([
            'certificate_expiry' => $user->certificate_expiry?->format('Y-m-d'),
            'license_expiry' => $user->license_expiry?->format('Y-m-d'),
            'id_expiry' => $user->id_expiry?->format('Y-m-d'),
            'is_certificate_valid' => (bool) $user->is_certificate_valid,
            'is_license_valid' => (bool) $user->is_license_valid,
            'is_id_valid' => (bool) $user->is_id_valid,
            'certificate_days_left' => $user->certificate_expiry ? $now->diffInDays($user->certificate_expiry, false) : null,
            'license_days_left' => $user->license_expiry ? $now->diffInDays($user->license_expiry, false) : null,
            'id_days_left' => $user->id_expiry ? $now->diffInDays($user->id_expiry, false) : null,
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/users/{user}/statistics',
        operationId: 'getUserStatistics',
        summary: 'عرض إحصايات المراقبين',
        description: 'استرجاع تفاصيل إحصايات مراقب معين',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                description: 'معرف المراقب',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصايات المراقب',
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
    public function statistics(User $user)
    {
        return StatisticResource::collection($user->statistics);
    }
}