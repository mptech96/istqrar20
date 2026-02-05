<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\V1\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Cookie;

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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.']
            ]);
        }

        if (!$user->status) {
            throw ValidationException::withMessages([
                'email' => ['الحساب غير نشط.']
            ]);
        }
        
        $user->tokens()->delete();
        
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                //'token' => $token
            ],
            'message' => 'تم تسجيل الدخول بنجاح'
        ])->withCookie(Cookie::make(
                'auth_token',
                encrypt($token),
                60 * 24, // يوم واحد
                null,    // path
                null,    // domain
                true,    // secure (HTTPS only)
                true,    // httpOnly
                false,   // raw
                'strict' // sameSite
            ));
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
        $request->user()->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ])->withCookie($cookie);
        //$request->user()->currentAccessToken()->delete();
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
            'data' => $request->user()->load(['company','branch'])
        ]);
    }
}
