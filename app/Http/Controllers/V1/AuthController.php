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
            
            //$userLogin->update(['password' => Hash::make($request->password)]);
            
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
}
