<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AiSuggestionResource;
use App\Models\V1\Visit;
use App\Models\V1\AiSuggestion;
use App\Services\V1\AI\DeepAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class DeepAnalysisController extends Controller
{
    protected $deepAnalysisService;

    public function __construct(DeepAnalysisService $deepAnalysisService)
    {
        $this->deepAnalysisService = $deepAnalysisService;
    }

    #[OA\Post(
        path: '/api/v1/visits/{visit}/deep-analysis',
        operationId: 'runDeepAnalysis',
        summary: 'تشغيل تحليل عميق للزيارة',
        description: 'يقوم بتحليل شامل باستخدام الذكاء الاصطناعي عند طلب المستخدم',
        tags: ['AI Analysis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم التحليل بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'summary', type: 'string'),
                        new OA\Property(property: 'analyses', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 202,
                description: 'التحليل قيد المعالجة (سيتم إعلامك عند الانتهاء)'
            ),
            new OA\Response(
                response: 429,
                description: 'تم تجاوز حد الطلبات المسموح به'
            ),
        ]
    )]
    public function analyze(Request $request, Visit $visit)
    {
        $user = auth()->user();

        // التحقق من صلاحية المستخدم
        if (!$this->canAccessAnalysis($user, $visit)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لتحليل هذه الزيارة'
            ], 403);
        }

        // التحقق من حد الطلبات اليومي
        $requestKey = "deep_analysis_requests_{$user->id}_" . now()->format('Y-m-d');
        $requestsToday = Cache::get($requestKey, 0);

        $maxRequests = setting('max_daily_deep_analysis', 5, 'ai');

        if ($requestsToday >= $maxRequests) {
            // return response()->json([
            //     'message' => "لقد تجاوزت حد التحليلات اليومي ({$maxRequests} تحليل)",
            //     'next_reset' => now()->endOfDay()->toIso8601String(),
            // ], 429);
        }

        // التحقق من وجود تحليل حديث
        $recentAnalysis = AiSuggestion::where('metric_name', 'deep_analysis')
            ->where('branch_id', $visit->branch_id)
            ->where('created_at', '>=', now()->subHours(setting('analysis_cooldown_hours', 24, 'ai')))
            ->latest()
            ->first();

        if (!$recentAnalysis && !$request->boolean('force')) {
            return response()->json([
                'message' => 'يوجد تحليل حديث لهذه الزيارة. استخدم force=true لإعادة التحليل',
                'data' => new AiSuggestionResource($recentAnalysis),
            ]);
        }

        // تنفيذ التحليل
        try {
            // تحديث عداد الطلبات
            Cache::put($requestKey, $requestsToday + 1, now()->endOfDay());

            // للتحليلات الطويلة، نستخدم Job
            /*if ($request->boolean('background')) {
                dispatch(new \App\Jobs\RunDeepAnalysisJob($visit, $user));

                return response()->json([
                    'message' => 'جاري تحليل الزيارة في الخلفية. سيتم إعلامك عند الانتهاء.',
                    'status' => 'processing',
                ], 202);
            }*/

            // تحليل فوري
            $result = $this->deepAnalysisService->analyzeVisit($visit, [
                'user_id' => $user->id,
                'request_id' => uniqid(),
            ]);

            return response()->json([
                'message' => 'تم التحليل بنجاح',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل التحليل: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/visits/{visit}/deep-analysis/latest',
        operationId: 'getLatestDeepAnalysis',
        summary: 'الحصول على آخر تحليل عميق',
        tags: ['AI Analysis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'اخر تحليل',
            ),
        ]
    )]
    public function latest(Visit $visit)
    {
        $analysis = AiSuggestion::where('metric_name', 'deep_analysis')
            ->where('branch_id', $visit->branch_id)
            ->latest()
            ->first();

        if (!$analysis) {
            return response()->json([
                'message' => 'لا يوجد تحليل عميق لهذه الزيارة'
            ], 404);
        }

        return response()->json([
            'data' => new AiSuggestionResource($analysis),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/visits/{visit}/deep-analysis/status',
        operationId: 'getDeepAnalysisStatus',
        summary: 'التحقق من حالة التحليل',
        tags: ['AI Analysis'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'حالة التحليل',
            ),
        ]
    )]
    public function status(Visit $visit)
    {
        $analysis = AiSuggestion::where('metric_name', 'deep_analysis')
            ->where('branch_id', $visit->branch_id)
            ->latest()
            ->first();

        return response()->json([
            'has_analysis' => !is_null($analysis),
            'analysis_id' => $analysis?->id,
            'analysis_date' => $analysis?->created_at?->toIso8601String(),
            'can_run_new' => $this->canRunNewAnalysis($visit),
            'remaining_requests' => $this->getRemainingRequests(),
        ]);
    }

    protected function canAccessAnalysis($user, Visit $visit): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCompanyAdmin()) {
            return $visit->branch->company_id === $user->company_id;
        }

        if ($user->isBranchManager()) {
            return $visit->branch_id === $user->branch_id;
        }

        if ($user->isAuditor()) {
            return $visit->auditor_id === $user->id;
        }

        return false;
    }

    protected function canRunNewAnalysis(Visit $visit): bool
    {
        $recent = AiSuggestion::where('metric_name', 'deep_analysis')
            ->where('branch_id', $visit->branch_id)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        return !$recent;
    }

    protected function getRemainingRequests(): int
    {
        $user = auth()->user();
        $maxRequests = setting('max_daily_deep_analysis', 5, 'ai');
        $requestKey = "deep_analysis_requests_{$user->id}_" . now()->format('Y-m-d');
        $requestsToday = Cache::get($requestKey, 0);

        return max(0, $maxRequests - $requestsToday);
    }
}