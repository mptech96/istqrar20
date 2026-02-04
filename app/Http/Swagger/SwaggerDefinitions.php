<?php

namespace App\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Quality System API",
    version: "1.0.0",
    description: "نظام إدارة الجودة والتدقيق - API Documentation",
)]

#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Local Server"
)]

#[OA\Server(
    url: "https://istqrar.flz.sa",
    description: "Production Server"
)]

#[OA\Tag(
    name: "Authentication",
    description: "عمليات المصادقة والتسجيل"
)]

#[OA\Tag(
    name: "Users",
    description: "إدارة المستخدمين"
)]

#[OA\Tag(
    name: "Companies",
    description: "إدارة الشركات"
)]

#[OA\Tag(
    name: "Branches",
    description: "إدارة فروع الشركات"
)]

#[OA\Tag(
    name: "Criterias",
    description: "إدارة معايير التقيمات"
)]

#[OA\Tag(
    name: "Visits",
    description: "إدارة الزيارات والتقييمات"
)]

#[OA\Tag(
    name: "Checklists",
    description: "إدارة قوائم المراجعة"
)]

#[OA\Tag(
    name: "Questions",
    description: "إدارة اسئلة قوائم المراجعة"
)]

#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]

class SwaggerDefinitions{}
