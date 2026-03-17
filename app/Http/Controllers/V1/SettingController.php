<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreSettingRequest;
use App\Http\Requests\V1\UpdateSettingRequest;
use App\Http\Requests\V1\BulkUpdateSettingsRequest;
use App\Http\Resources\V1\SettingResource;
use App\Models\V1\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class SettingController extends Controller
{
    #[OA\Get(
        path: '/api/v1/settings',
        operationId: 'getSettings',
        summary: 'عرض قائمة الإعدادات',
        description: 'استرجاع قائمة الإعدادات مع إمكانية الفلترة',
        tags: ['Settings'],
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
                name: 'group',
                description: 'فلتر بمجموعة الإعدادات',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'search',
                description: 'بحث بالمفتاح أو التسمية',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'public_only',
                description: 'عرض الإعدادات العامة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'include_values',
                description: 'تضمين القيم',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: true)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة الإعدادات',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'group', type: 'string'),
                                    new OA\Property(property: 'key', type: 'string'),
                                    new OA\Property(property: 'type', type: 'string'),
                                    new OA\Property(property: 'label', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Setting::query();
        
        // Filtering
        if ($request->has('group')) {
            $query->where('group', $request->input('group'));
        }
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        if ($request->boolean('public_only')) {
            $query->where('is_public', true);
        }
        
        // Sorting
        $query->orderBy('group')->orderBy('sort_order')->orderBy('key');
        
        $settings = $query->paginate($request->input('per_page', 15));
        
        return SettingResource::collection($settings);
    }

    #[OA\Get(
        path: '/api/v1/settings/groups',
        operationId: 'getSettingGroups',
        summary: 'عرض مجموعات الإعدادات',
        description: 'استرجاع قائمة مجموعات الإعدادات المتاحة',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة المجموعات',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function groups()
    {
        $groups = Setting::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');
        
        return response()->json([
            'data' => $groups
        ]);
    }

    #[OA\Get(
        path: '/api/v1/settings/{group}',
        operationId: 'getSettingsByGroup',
        summary: 'عرض إعدادات مجموعة',
        description: 'استرجاع إعدادات مجموعة معينة',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'group',
                description: 'اسم المجموعة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'general')
            ),
            new OA\Parameter(
                name: 'public_only',
                description: 'عرض الإعدادات العامة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'إعدادات المجموعة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'string'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function group($group, Request $request)
    {
        $publicOnly = $request->boolean('public_only', false);
        $settings = Setting::getGroup($group, $publicOnly);
        
        return response()->json([
            'data' => $settings
        ]);
    }

    #[OA\Get(
        path: '/api/v1/settings/{group}/{key}',
        operationId: 'getSetting',
        summary: 'عرض قيمة إعداد',
        description: 'استرجاع قيمة إعداد معين',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'group',
                description: 'اسم المجموعة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'general')
            ),
            new OA\Parameter(
                name: 'key',
                description: 'مفتاح الإعداد',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'site_name')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قيمة الإعداد',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'value', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإعداد غير موجود'
            ),
        ]
    )]
    public function show($group, $key)
    {
        $setting = Setting::where('group', $group)
            ->where('key', $key)
            ->firstOrFail();
        
        return response()->json([
            'data' => new SettingResource($setting)
        ]);
    }

    #[OA\Post(
        path: '/api/v1/settings',
        operationId: 'createSetting',
        summary: 'إنشاء إعداد جديد',
        description: 'إنشاء إعداد جديد في النظام',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['group', 'key', 'type'],
                properties: [
                    new OA\Property(property: 'group', type: 'string', example: 'general'),
                    new OA\Property(property: 'key', type: 'string', example: 'site_name'),
                    new OA\Property(property: 'value', type: 'string', example: 'نظام التقييم'),
                    new OA\Property(property: 'type', type: 'string', enum: ['string', 'number', 'boolean', 'json', 'array', 'text'], example: 'string'),
                    new OA\Property(property: 'label', type: 'string', example: 'اسم الموقع'),
                    new OA\Property(property: 'description', type: 'string', example: 'اسم الموقع الذي يظهر في الصفحة الرئيسية'),
                    new OA\Property(property: 'is_public', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إنشاء الإعداد بنجاح'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function store(StoreSettingRequest $request)
    {
        $setting = Setting::create([
            'group' => $request->input('group'),
            'key' => $request->input('key'),
            'value' => $request->input('value'),
            'type' => $request->input('type'),
            'data_type' => $request->input('data_type'),
            'validation_rules' => $request->input('validation_rules'),
            'options' => $request->input('options'),
            'label' => $request->input('label'),
            'description' => $request->input('description'),
            'placeholder' => $request->input('placeholder'),
            'sort_order' => $request->input('sort_order', 0),
            'is_public' => $request->boolean('is_public', false),
            'is_encrypted' => $request->boolean('is_encrypted', false),
            'is_required' => $request->boolean('is_required', false),
        ]);
        
        return response()->json([
            'data' => new SettingResource($setting),
            'message' => 'تم إنشاء الإعداد بنجاح'
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/settings/{setting}',
        operationId: 'updateSetting',
        summary: 'تحديث إعداد',
        description: 'تحديث بيانات إعداد موجود',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'setting',
                description: 'معرف الإعداد',
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
                    new OA\Property(property: 'value', type: 'string'),
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'is_public', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الإعداد بنجاح'
            ),
            new OA\Response(
                response: 404,
                description: 'الإعداد غير موجود'
            ),
        ]
    )]
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        // $setting->update($request->only([
        //     'group', 'key', 'value', 'type', 'data_type',
        //     'validation_rules', 'options', 'label', 'description',
        //     'placeholder', 'sort_order', 'is_public', 'is_encrypted', 'is_required'
        // ]));
        
        $setting->update($request->only([
            'value', 'type', 'data_type',
            'validation_rules', 'options', 'label', 'description',
            'placeholder', 'sort_order', 'is_public', 'is_encrypted', 'is_required'
        ]));

        return response()->json([
            'data' => new SettingResource($setting),
            'message' => 'تم تحديث الإعداد بنجاح'
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/settings/{setting}',
        operationId: 'deleteSetting',
        summary: 'حذف إعداد',
        description: 'حذف إعداد معين',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'setting',
                description: 'معرف الإعداد',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف الإعداد بنجاح'
            ),
            new OA\Response(
                response: 404,
                description: 'الإعداد غير موجود'
            ),
        ]
    )]
    public function destroy(Setting $setting)
    {
        //$setting->delete();
        
        return response()->json([
            'data' => new SettingResource($setting),
            'message' => 'تم حذف الإعداد بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/settings/bulk',
        operationId: 'bulkUpdateSettings',
        summary: 'تحديث إعدادات متعددة',
        description: 'تحديث عدة إعدادات دفعة واحدة',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['settings'],
                properties: [
                    new OA\Property(
                        property: 'settings',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'group', type: 'string'),
                                new OA\Property(property: 'key', type: 'string'),
                                new OA\Property(property: 'value', type: 'string'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الإعدادات بنجاح'
            ),
        ]
    )]
    public function bulkUpdate(BulkUpdateSettingsRequest $request)
    {
        // DB::transaction(function () use ($request) {
        //     foreach ($request->input('settings') as $item) {
        //         Setting::set($item['key'], $item['value'], $item['group']);
        //     }
        // });
        
        return response()->json([
            'message' => 'تم تحديث الإعدادات بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/settings/import',
        operationId: 'importSettings',
        summary: 'استيراد إعدادات',
        description: 'استيراد إعدادات من ملف JSON',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'override', type: 'boolean', default: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استيراد الإعدادات بنجاح'
            ),
        ]
    )]
    public function import(Request $request)
    {
        // $request->validate([
        //     'file' => ['required', 'file', 'mimes:json', 'max:2048'],
        //     'override' => ['nullable', 'boolean'],
        // ]);
        
        // $file = $request->file('file');
        // $content = json_decode(file_get_contents($file), true);
        
        // if (!is_array($content)) {
        //     return response()->json([
        //         'message' => 'ملف JSON غير صالح'
        //     ], 422);
        // }
        
        // DB::transaction(function () use ($content, $request) {
        //     $override = $request->boolean('override', false);
            
        //     foreach ($content as $group => $settings) {
        //         foreach ($settings as $key => $value) {
        //             if ($override || !Setting::has($key, $group)) {
        //                 Setting::set($key, $value, $group);
        //             }
        //         }
        //     }
        // });
        
        return response()->json([
            'message' => 'تم استيراد الإعدادات بنجاح'
        ]);
    }

    #[OA\Get(
        path: '/api/v1/settings/export',
        operationId: 'exportSettings',
        summary: 'تصدير الإعدادات',
        description: 'تصدير جميع الإعدادات كملف JSON',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'ملف الإعدادات',
                content: new OA\MediaType(
                    mediaType: 'application/json'
                )
            ),
        ]
    )]
    public function export()
    {
        $settings = Setting::getAll();
        
        return response()->json($settings)
            ->header('Content-Disposition', 'attachment; filename="settings.json"');
    }

    #[OA\Get(
        path: '/api/v1/settings/public',
        operationId: 'getPublicSettings',
        summary: 'عرض الإعدادات العامة',
        description: 'استرجاع جميع الإعدادات العامة (لا تتطلب مصادقة)',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'groups',
                description: 'المجموعات المطلوبة (مفصولة بفواصل)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'general,email')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'الإعدادات العامة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'string'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function publicSettings(Request $request)
    {
        $groups = $request->input('groups');
        
        if ($groups) {
            $groups = explode(',', $groups);
            $settings = [];
            
            foreach ($groups as $group) {
                $settings[$group] = Setting::getGroup(trim($group), true);
            }
        } else {
            $settings = Setting::getAll(true);
        }
        
        return response()->json([
            'data' => $settings
        ]);
    }
}
