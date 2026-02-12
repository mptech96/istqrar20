<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CompanyRequest;
use App\Http\Resources\V1\CompanyResource;
use App\Http\Resources\V1\StatisticResource;
use App\Models\V1\Company;
use App\Models\V1\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class CompanyController extends Controller
{
    #[OA\Get(
        path: '/api/v1/companies',
        operationId: 'getCompanies',
        summary: 'عرض قائمة الشركات',
        description: 'استرجاع قائمة الشركات مع إمكانية الفلترة والترتيب',
        tags: ['Companies'],
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
                name: 'status',
                description: 'فلتر بحالة الشركة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'subscription_status',
                description: 'فلتر بحالة الاشتراك',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'suspended'])
            ),
            new OA\Parameter(
                name: 'category',
                description: 'فلتر بالتصنيف',
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
                    enum: ['name', 'email', 'created_at', 'subscription_end']
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
                description: 'قائمة الشركات',
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
                                    new OA\Property(property: 'name', type: 'string', example: 'شركة الجودة'),
                                    new OA\Property(property: 'email', type: 'string', example: 'info@company.com'),
                                    new OA\Property(property: 'phone', type: 'string', example: '+966500000000'),
                                    new OA\Property(property: 'status', type: 'boolean', example: true),
                                    new OA\Property(property: 'subscription_status', type: 'string', example: 'active'),
                                    new OA\Property(property: 'subscription_end', type: 'string', format: 'date', example: '2026-12-31'),
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
        $query = Company::query();
        
        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }
        
        if ($request->has('subscription_status')) {
            $query->where('subscription_status', $request->input('subscription_status'));
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $companies = $query->with(['manager'])->paginate($request->input('per_page', 15));
        
        return CompanyResource::collection($companies);
    }
    
    #[OA\Post(
        path: '/api/v1/companies',
        operationId: 'createCompany',
        summary: 'إنشاء شركة جديدة مع مديرها',
        description: 'إنشاء شركة جديدة مع بيانات المدير المرتبط بها',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: [
                        'name', 'email', 'phone', 'commercial_registration', 'city', 'address',
                        'contact_person', 'subscription_start', 'subscription_end',
                        'subscription_status', 'category', 
                        'user_name', 'user_email', 'user_password', 'user_password_confirmation'
                    ],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'company@example.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+966500000000'),
                        new OA\Property(property: 'commercial_registration', type: 'string', example: '1010123456'),
                        new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                        new OA\Property(property: 'address', type: 'string', example: 'حي النخيل، شارع الملك فهد'),
                        new OA\Property(property: 'contact_person', type: 'string', example: 'أحمد محمد'),
                        new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                        new OA\Property(property: 'subscription_start', type: 'string', format: 'date', example: '2024-01-01'),
                        new OA\Property(property: 'subscription_end', type: 'string', format: 'date', example: '2024-12-31'),
                        new OA\Property(
                            property: 'subscription_status',
                            type: 'string',
                            enum: ['active', 'inactive', 'suspended'],
                            example: 'active'
                        ),
                        new OA\Property(property: 'category', type: 'string', example: 'تقنية المعلومات'),
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'is_verified', type: 'boolean', example: false),
                        
                        new OA\Property(property: 'user_name', type: 'string', example: 'مدير الشركة'),
                        new OA\Property(property: 'user_email', type: 'string', format: 'email', example: 'manager@company.com'),
                        new OA\Property(property: 'user_password', type: 'string', format: 'password'),
                        new OA\Property(property: 'user_password_confirmation', type: 'string', format: 'password'),
                        new OA\Property(property: 'user_phone', type: 'string', example: '+966511111111'),
                        new OA\Property(property: 'user_personal_photo', type: 'file', format: 'binary'),
                        new OA\Property(property: 'user_birth_date', type: 'string', format: 'date', example: '1990-01-01'),
                        new OA\Property(property: 'user_gender', type: 'integer', enum: [1, 2, 3], example: 1),
                        new OA\Property(property: 'user_status', type: 'boolean', example: true),
    
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء الشركة بنجاح',
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
                                new OA\Property(property: 'status', type: 'boolean'),
                                new OA\Property(property: 'subscription_status', type: 'string'),
                                new OA\Property(property: 'subscription_end', type: 'string', format: 'date'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء الشركة بنجاح'),
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
    
    public function store(CompanyRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Handle company logo upload
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = __saveFile($request->file('logo'), base_path('public/uploads/companies/logos/'));
            }
            
            // Create company
            $company = Company::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'commercial_registration' => $request->input('commercial_registration'),
                'city' => $request->input('city'),
                'address' => $request->input('address'),
                'contact_person' => $request->input('contact_person'),
                'logo' => $logoPath,
                'subscription_start' => $request->input('subscription_start'),
                'subscription_end' => $request->input('subscription_end'),
                'subscription_status' => $request->input('subscription_status'),
                'category' => $request->input('category'),
                //'status' => $request->input('status', true),
                //'is_verified' => $request->input('is_verified', false),
            ]);
            
            // Handle manager personal_photo upload
            $personal_photoPath = null;
            if ($request->hasFile('user_personal_photo')) {
                $personal_photoPath = __saveFile($request->file('user_personal_photo'), base_path('public/uploads/users/personal_photos/'));
            }
            
            // Create company manager user
            $manager = $company->manager()->create([
                'name' => $request->input('user_name'),
                'email' => $request->input('user_email'),
                'password' => Hash::make($request->input('user_password')),
                'phone' => $request->input('user_phone'),
                'personal_photo' => $personal_photoPath,
                'birth_date' => $request->input('user_birth_date'),
                'gender' => $request->input('user_gender'),
                //'status' => $request->input('user_status', true),
                //'company_id' => $company->id,
                //'created_by' => ,
                'role' => 'company_admin',
            ]);
            
            return new CompanyResource($company->load(['manager']));
        });
    }
    
    #[OA\Get(
        path: '/api/v1/companies/{company}',
        operationId: 'getCompany',
        summary: 'عرض تفاصيل شركة',
        description: 'استرجاع تفاصيل شركة معينة',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'معرف الشركة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'branches,users')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل الشركة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                                new OA\Property(property: 'email', type: 'string', example: 'company@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000000'),
                                new OA\Property(property: 'commercial_registration', type: 'string', example: '1010123456'),
                                new OA\Property(property: 'city', type: 'string', example: 'الرياض'),
                                new OA\Property(property: 'address', type: 'string', example: 'حي النخيل، شارع الملك فهد'),
                                new OA\Property(property: 'contact_person', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                                new OA\Property(property: 'subscription_start', type: 'string', format: 'date', example: '2024-01-01'),
                                new OA\Property(property: 'subscription_end', type: 'string', format: 'date', example: '2024-12-31'),
                                new OA\Property(property: 'subscription_status', type: 'string', example: 'active'),
                                new OA\Property(property: 'category', type: 'string', example: 'تقنية المعلومات'),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'is_verified', type: 'boolean', example: false),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الشركة غير موجودة'
            ),
        ]
    )]
    
    public function show(Company $company)
    {
        $company->load(['branches', 'users', 'visits']);
        return new CompanyResource($company);
    }
    
    #[OA\Put(
        path: '/api/v1/companies/{company}',
        operationId: 'updateCompany',
        summary: 'تحديث بيانات شركة',
        description: 'تحديث بيانات شركة موجودة',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'معرف الشركة',
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
                        new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'updated@example.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+966500000001'),
                        new OA\Property(property: 'city', type: 'string', example: 'جدة'),
                        new OA\Property(property: 'address', type: 'string', example: 'حي الصفا، شارع الأمير سلطان'),
                        new OA\Property(property: 'contact_person', type: 'string', example: 'محمد أحمد'),
                        new OA\Property(property: 'logo', type: 'string', format: 'binary', description: 'شعار الشركة'),
                        new OA\Property(
                            property: 'subscription_status',
                            type: 'string',
                            enum: ['active', 'inactive', 'suspended'],
                            example: 'active'
                        ),
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'is_verified', type: 'boolean', example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الشركة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث الشركة بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                                new OA\Property(property: 'email', type: 'string', example: 'updated@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000001'),
                                new OA\Property(property: 'city', type: 'string', example: 'جدة'),
                                new OA\Property(property: 'address', type: 'string', example: 'حي الصفا، شارع الأمير سلطان'),
                                new OA\Property(property: 'contact_person', type: 'string', example: 'محمد أحمد'),
                                new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                                new OA\Property(property: 'subscription_status', type: 'string', example: 'active'),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'is_verified', type: 'boolean', example: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الشركة غير موجودة'
            ),
        ]
    )]
    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:companies,email,' . $company->id],
            'phone' => ['sometimes', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'status' => ['nullable'],
            'subscription_status' => ['nullable', 'string', 'in:active,inactive,suspended'],
        ]);
    
        $request->merge([
            'status' => filter_var($request->input('status', false), FILTER_VALIDATE_BOOLEAN)
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                delete_file_req($company->logo, base_path('public/uploads/companies/logos/'));
            }
            $logoPath = __saveFile($request->file('logo'), base_path('public/uploads/companies/logos/'));
            $company->logo = $logoPath;
        }
        
        $company->update($request->only([
            'name', 'email', 'phone', 'subscription_status', 'status'
        ]));
        
        return new CompanyResource($company->load(['manager']));
    }
    
    #[OA\Delete(
        path: '/api/v1/companies/{company}',
        operationId: 'deleteCompany',
        summary: 'حذف شركة',
        description: 'حذف شركة معينة (حذف ناعم)',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'معرف الشركة',
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
                description: 'تم حذف الشركة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف الشركة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                                new OA\Property(property: 'email', type: 'string', example: 'company@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000000'),
                                new OA\Property(property: 'status', type: 'boolean', example: false),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الشركة غير موجودة'
            ),
        ]
    )]
    
    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'تم حذف الشركة بنجاح']);
    }
    
    #[OA\Post(
        path: '/api/v1/companies/{company}/restore',
        operationId: 'restoreCompany',
        summary: 'استعادة شركة محذوفة',
        description: 'استعادة شركة تم حذفها (حذف ناعم)',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'معرف الشركة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة الشركة بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة الشركة بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية المحدودة'),
                                new OA\Property(property: 'email', type: 'string', example: 'company@example.com'),
                                new OA\Property(property: 'phone', type: 'string', example: '+966500000000'),
                                new OA\Property(property: 'status', type: 'boolean', example: true),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الشركة غير موجودة'
            ),
        ]
    )]
    public function restore($id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->restore();
        return new CompanyResource($company);
    }
    
    #[OA\Get(
        path: '/api/v1/companies/{company}/statistics',
        operationId: 'getCompanyStatistics',
        summary: 'عرض إحصايات الشركات',
        description: 'استرجاع تفاصيل إحصايات شركة معينة',
        tags: ['Companies'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'company',
                description: 'معرف الشركة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إحصايات الشركة',
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
    public function statistics(Company $company)
    {
        return StatisticResource::collection($company->statistics);
    }
}
