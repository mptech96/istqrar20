<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\V1\Company;
use OpenApi\Attributes as OA;

/**
 * بيانات عامة للموقع التعريفي (بدون تسجيل دخول)
 */
class PublicController extends Controller
{
    #[OA\Get(
        path: "/api/v1/public/clients",
        summary: "عملاؤنا",
        description: "قائمة المنشآت المتعاقدة التي فعّل مدير النظام ظهورها في صفحة (عملاؤنا) بالموقع العام: الاسم والشعار والمدينة فقط",
        tags: ["Public"],
        responses: [new OA\Response(response: 200, description: "قائمة العملاء")]
    )]
    public function clients()
    {
        // الزائر غير مسجل، لذا يُتجاوز نطاق الصلاحيات ويُعرض فقط ما فعّله مدير النظام
        $clients = Company::withoutGlobalScope('companies')
            ->where('status', 1)
            ->where('show_on_website', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'logo', 'city'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'city' => $c->city,
                'logo' => $c->logo ? asset('uploads/companies/logos/' . $c->logo) : null,
            ]);

        return response()->json(['data' => $clients]);
    }
}
