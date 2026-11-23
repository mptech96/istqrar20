<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User as UserLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use App\Models\V1\User;

use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // ------------------------ LOGIN ------------------------
    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "تسجيل الدخول",
        description: "تسجيل دخول المستخدم والحصول على token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(
                    type: "object",
                    required: ["email","password"],
                    properties: [
                        new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم تسجيل الدخول بنجاح",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "user", type: "object", properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                                new OA\Property(property: "email", type: "string", example: "admin@example.com"),
                                new OA\Property(property: "role", type: "string", example: "auditor"),
                                new OA\Property(property: "status", type: "boolean", example: true),
                            ]),
                            new OA\Property(property: "token", type: "string", example: "1|randomtoken123")
                        ]),
                        new OA\Property(property: "message", type: "string", example: "تم تسجيل الدخول بنجاح")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "مصادقة فاشلة"),
            new OA\Response(response: 422, description: "بيانات غير صالحة")
        ]
    )]
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
    
            $userLogin = UserLogin::where('email', $request->email)->first();
            
            // $userLogin->update(['password' => Hash::make($request->password)]);
            
            if (!$userLogin || !Hash::check($request->password, $userLogin->password)) {
                
                app(\App\Http\Middleware\LoginThrottleMiddleware::class)->handleLoginAttempt($request->email, $request->ip(), false);
                
                throw ValidationException::withMessages([
                    'email' => ['بيانات الدخول غير صحيحة.']
                ]);
            }

            Auth::setUser($userLogin);

            $user = User::find($userLogin->id);

            if (!$user->status) {
                throw ValidationException::withMessages([
                    'email' => ['الحساب غير نشط.']
                ]);
            }
            
            if (($user->role === 'company_admin' && !$user->company?->getActiveSubscription()) || ($user->role === 'branch_manager' && !$user->branch?->company?->getActiveSubscription())) {
                return response()->json([
                    'success' => false,
                    'message' => 'الإشتراك غير صالح'
                ]);
            }
            //$userLogin->tokens()->delete();
            
            $userLogin->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);
    
            $token = encrypt($userLogin->createToken("auth_token")->plainTextToken);
            
            app(\App\Http\Middleware\LoginThrottleMiddleware::class)->handleLoginAttempt($request->email, $request->ip(), true);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userLogin,
                    'token' => $token
                ],
                'message' => 'تم تسجيل الدخول بنجاح'
            ])->withCookie(Cookie::make(
                    'auth_token',
                    $token,
                    60 * 24, // يوم واحد
                    null,    // path
                    null,    // domain
                    true,    // secure (HTTPS only)
                    true,    // httpOnly
                    false,   // raw
                    'none' // sameSite
                ));
        }
        catch (\Exception $e) {}
        throw ValidationException::withMessages([
            'email' => ['بيانات الدخول غير صحيحة.']
         ]);
    }

    // ------------------------ LOGOUT ------------------------
    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "تسجيل الخروج",
        description: "إلغاء token المستخدم الحالي",
        tags: ["Authentication"],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: "تم تسجيل الخروج بنجاح")
        ]
    )]
    public function logout(Request $request)
    {
        $cookie = Cookie::forget('auth_token');
        //$request->user()->tokens()->delete();
        //$request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ])->withCookie(Cookie::make(
            'auth_token',
            '',
            null,
            null,    // path
            null,    // domain
            true,    // secure (HTTPS only)
            true,    // httpOnly
            false,   // raw
            'none' // sameSite
        ));
    }

    // ------------------------ ME ------------------------
    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "بيانات المستخدم الحالي",
        description: "الحصول على بيانات المستخدم المسجل دخوله",
        tags: ["Authentication"],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "بيانات المستخدم",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "أحمد محمد"),
                            new OA\Property(property: "email", type: "string", example: "ahmed@example.com"),
                            new OA\Property(property: "role", type: "string", example: "auditor"),
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "company_id", type: "integer", example: 1),
                            new OA\Property(property: "branch_id", type: "integer", example: 1)
                        ])
                    ]
                )
            )
        ]
    )]
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }


    #[OA\Post(
        path: '/api/v1/auth/update',
        summary: 'تحديث بيانات المستخدم',
        description: 'تحديث بيانات المستخدم الحالي',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: ''),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: ''),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: ''),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: ''),
                        new OA\Property(property: 'phone', type: 'string', example: ''),
                        new OA\Property(property: 'municipality_certificate', type: 'file', format: 'binary'),
                        new OA\Property(property: 'driving_license', type: 'file', format: 'binary'),
                        new OA\Property(property: 'personal_photo', type: 'file', format: 'binary'),
                        new OA\Property(property: 'national_number', type: 'string', example: ''),
                        new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: ''),
                        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], example: 'male'),
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
    public function update(UpdateUserRequest $request)
    {
        return DB::transaction(function () use ($request) {
            
            $user = User::find(auth()->id());
            
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
            $user->update(
                collect($request->only([
                    'name', 'email', 'phone',
                    'national_number', 'birth_date', 'gender',
                ]))->filter(fn($value) => filled($value))->toArray()
            );

            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
                $user->tokens()->delete();
            }
            
            if ($request->has('must_change_password')) {
                $user->must_change_password = filter_var($request->input('must_change_password'), FILTER_VALIDATE_BOOLEAN);
            }
            
            $user->save();
            
            return response()->json([
                'data' => new UserResource($user),
                'message' => 'تم تحديث المستخدم بنجاح'
            ]);
        });
    }
}
