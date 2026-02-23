<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BranchResource;
use App\Http\Resources\V1\CompanyResource;
use App\Http\Resources\V1\TicketResource;
use App\Http\Resources\V1\VisitResource;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\AnswerResource;
use App\Models\V1\Company;
use App\Models\V1\Branch;
use App\Models\V1\Visit;
use App\Models\V1\User;
use App\Models\V1\Ticket;
use App\Models\V1\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    /**
     * تقرير شامل لمدير النظام (super_admin)
     */
    #[OA\Get(
        path: '/api/v1/reports/super-admin',
        operationId: 'superAdminReport',
        summary: 'تقرير شامل لمدير النظام',
        description: 'إحصائيات عامة عن النظام، توزيع الزيارات، أداء الشركات، التذاكر، وأحدث الأنشطة',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                description: 'السنة المطلوبة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
            new OA\Parameter(
                name: 'month',
                description: 'الشهر المطلوب (1-12)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تقرير مدير النظام',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'overall_statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_companies', type: 'integer', example: 25),
                                new OA\Property(property: 'total_branches', type: 'integer', example: 120),
                                new OA\Property(property: 'total_users', type: 'integer', example: 300),
                                new OA\Property(property: 'total_auditors', type: 'integer', example: 45),
                                new OA\Property(property: 'total_visits', type: 'integer', example: 850),
                                new OA\Property(property: 'completed_visits', type: 'integer', example: 600),
                                new OA\Property(property: 'avg_percentage', type: 'number', format: 'float', example: 78.5),
                            ]
                        ),
                        new OA\Property(
                            property: 'visits_by_category',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'integer')
                        ),
                        new OA\Property(
                            property: 'tickets_summary',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 120),
                                new OA\Property(property: 'open', type: 'integer', example: 35),
                                new OA\Property(property: 'overdue', type: 'integer', example: 8),
                            ]
                        ),
                        new OA\Property(
                            property: 'top_companies',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية'),
                                    new OA\Property(property: 'avg_percentage', type: 'number', format: 'float', example: 92.5),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مدير نظام'
            ),
        ]
    )]
    public function superAdminReport(Request $request)
    {
        // التأكد من الدور (يمكن استخدام Gates)
        if (auth()->user()->role !== 'super_admin') {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month') ? (int) $request->input('month') : null;

        // إحصائيات عامة
        $totalCompanies = Company::count();
        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $totalAuditors = User::where('role', 'auditor')->count();
        $totalVisits = Visit::count();
        $completedVisits = Visit::where('status', 'completed')->count();
        $avgPercentage = Visit::whereNotNull('percentage')->avg('percentage') ?? 0;

        // توزيع الزيارات حسب الفئة
        $visitsByCategory = Visit::whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        // إحصائيات التذاكر
        $ticketsSummary = [
            'total' => Ticket::count(),
            'open' => Ticket::open()->count(),
            'overdue' => Ticket::overdue()->count(),
        ];

        // أفضل 5 شركات حسب متوسط الأداء
        $topCompanies = Company::whereHas('branches.visits', function ($q) use ($year, $month) {
                $q->whereNotNull('visits.percentage');
                if ($month) {
                    $q->whereYear('visits.created_at', $year)
                      ->whereMonth('visits.created_at', $month);
                } else {
                    $q->whereYear('visits.created_at', $year);
                }
            })
            ->withAvg(['visits as avg_percentage' => function ($q) use ($year, $month) {
                $q->whereNotNull('visits.percentage');
                if ($month) {
                    $q->whereYear('visits.created_at', $year)
                      ->whereMonth('visits.created_at', $month);
                } else {
                    $q->whereYear('visits.created_at', $year);
                }
            }], 'percentage')
            ->having('avg_percentage', '>', 0)
            ->orderByDesc('avg_percentage')
            ->limit(5)
            ->get(['id', 'name', 'avg_percentage']);

        // أحدث الزيارات (10)
        $latestVisits = Visit::with(['branch', 'auditor'])
            ->latest()
            ->limit(10)
            ->get();

        // أحدث التذاكر (10)
        $latestTickets = Ticket::with(['creator', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'overall_statistics' => [
                'total_companies' => $totalCompanies,
                'total_branches' => $totalBranches,
                'total_users' => $totalUsers,
                'total_auditors' => $totalAuditors,
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'avg_percentage' => round($avgPercentage, 2),
            ],
            'visits_by_category' => $visitsByCategory,
            'tickets_summary' => $ticketsSummary,
            'top_companies' => $topCompanies,
            'latest_visits' => VisitResource::collection($latestVisits),
            'latest_tickets' => TicketResource::collection($latestTickets),
        ]);
    }

    /**
     * تقرير مدير الشركة (company_admin)
     */
    #[OA\Get(
        path: '/api/v1/reports/company-admin',
        operationId: 'companyAdminReport',
        summary: 'تقرير مدير الشركة',
        description: 'إحصائيات خاصة بشركة المستخدم الحالي، أداء الفروع، التصحيحات المعلقة، التذاكر المفتوحة',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                description: 'السنة المطلوبة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
            new OA\Parameter(
                name: 'month',
                description: 'الشهر المطلوب (1-12)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
            new OA\Parameter(
                name: 'branch_id',
                description: 'فلتر بمعرف فرع محدد',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تقرير مدير الشركة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'company_info',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'شركة التقنية'),
                                new OA\Property(property: 'overall_score', type: 'number', format: 'float', example: 82.5),
                            ]
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'branches_count', type: 'integer', example: 5),
                                new OA\Property(property: 'auditors_count', type: 'integer', example: 8),
                                new OA\Property(property: 'branch_managers_count', type: 'integer', example: 5),
                                new OA\Property(property: 'total_visits', type: 'integer', example: 42),
                                new OA\Property(property: 'completed_visits', type: 'integer', example: 30),
                                new OA\Property(property: 'avg_company_score', type: 'number', format: 'float', example: 81.3),
                            ]
                        ),
                        new OA\Property(
                            property: 'branches_performance',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                    new OA\Property(property: 'last_visit_percentage', type: 'number', format: 'float', example: 85.0),
                                    new OA\Property(property: 'category', type: 'string', example: 'B'),
                                    new OA\Property(property: 'total_visits', type: 'integer', example: 10),
                                    new OA\Property(property: 'avg_score', type: 'number', format: 'float', example: 82.0),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'pending_corrections',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'branch', type: 'string', example: 'الفرع الرئيسي'),
                                    new OA\Property(property: 'question', type: 'string', example: 'هل تم تنظيف الممرات؟'),
                                    new OA\Property(property: 'deadline', type: 'string', format: 'date', example: '2026-02-28'),
                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مدير شركة أو ليس له شركة'
            ),
        ]
    )]
    public function companyAdminReport(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'company_admin' || !$user->company_id) {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }

        $companyId = $user->company_id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month') ? (int) $request->input('month') : null;
        $branchId = $request->input('branch_id');

        // بيانات الشركة
        $company = Company::find($companyId);

        // إحصائيات الشركة
        $branchesCount = Branch::where('company_id', $companyId)->count();
        $auditorsCount = User::where('company_id', $companyId)->where('role', 'auditor')->count();
        $branchManagersCount = User::where('company_id', $companyId)->where('role', 'branch_manager')->count();

        $visitsQuery = Visit::whereHas('branch', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('id', $branchId);
            }
        });

        if ($month) {
            $visitsQuery->whereYear('visits.created_at', $year)
                       ->whereMonth('visits.created_at', $month);
        } elseif ($year) {
            $visitsQuery->whereYear('visits.created_at', $year);
        }

        $totalVisits = (clone $visitsQuery)->count();
        $completedVisits = (clone $visitsQuery)->where('visits.status', 'completed')->count();
        $avgCompanyScore = (clone $visitsQuery)->whereNotNull('visits.percentage')->avg('visits.percentage') ?? 0;

        // أداء الفروع
        $branches = Branch::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['visits' => function ($q) use ($year, $month) {
                $q->whereNotNull('visits.percentage');
                if ($month) {
                    $q->whereYear('visits.created_at', $year)
                      ->whereMonth('visits.created_at', $month);
                } elseif ($year) {
                    $q->whereYear('visits.created_at', $year);
                }
            }])
            ->get();

        $branchesPerformance = $branches->map(function ($branch) {
            $lastVisit = $branch->visits->sortByDesc('created_at')->first();
            $avgScore = $branch->visits->avg('percentage') ?? 0;
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'last_visit_percentage' => $lastVisit?->percentage,
                'category' => $branch->category,
                'total_visits' => $branch->visits->count(),
                'avg_score' => round($avgScore, 2),
            ];
        });

        // التصحيحات المعلقة
        $pendingCorrections = Answer::whereHas('visit.branch', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId);
                if ($branchId) {
                    $q->where('id', $branchId);
                }
            })
            ->where('correction_status', 'pending')
            ->with(['question', 'visit.branch'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'branch' => $answer->visit->branch->name,
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'deadline' => $answer->corrective_deadline?->format('Y-m-d'),
                    'is_overdue' => $answer->isOverdue(),
                ];
            });

        // التذاكر المفتوحة
        $openTickets = Ticket::where('company_id', $companyId)
            ->open()
            ->with(['creator', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'company_info' => [
                'id' => $company->id,
                'name' => $company->name,
                'overall_score' => $company->overall_score,
            ],
            'statistics' => [
                'branches_count' => $branchesCount,
                'auditors_count' => $auditorsCount,
                'branch_managers_count' => $branchManagersCount,
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'avg_company_score' => round($avgCompanyScore, 2),
            ],
            'branches_performance' => $branchesPerformance,
            'pending_corrections' => $pendingCorrections,
            'open_tickets' => TicketResource::collection($openTickets),
        ]);
    }

    /**
     * تقرير مدير الفرع (branch_manager)
     */
    #[OA\Get(
        path: '/api/v1/reports/branch-manager',
        operationId: 'branchManagerReport',
        summary: 'تقرير مدير الفرع',
        description: 'إحصائيات خاصة بالفرع، التصحيحات المطلوبة، التذاكر المفتوحة، آخر الزيارات',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                description: 'السنة المطلوبة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
            new OA\Parameter(
                name: 'month',
                description: 'الشهر المطلوب (1-12)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تقرير مدير الفرع',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'branch_info',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                new OA\Property(property: 'compliance_score', type: 'number', format: 'float', example: 82.5),
                                new OA\Property(property: 'category', type: 'string', example: 'B'),
                                new OA\Property(property: 'last_visit_date', type: 'string', format: 'date', example: '2026-02-10'),
                                new OA\Property(property: 'next_scheduled_visit', type: 'string', format: 'date', example: '2026-03-01'),
                            ]
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_visits', type: 'integer', example: 15),
                                new OA\Property(property: 'avg_score', type: 'number', format: 'float', example: 78.3),
                                new OA\Property(property: 'visits_this_month', type: 'integer', example: 3),
                            ]
                        ),
                        new OA\Property(
                            property: 'pending_corrections',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'question', type: 'string', example: 'هل تم تنظيف الممرات؟'),
                                    new OA\Property(property: 'corrective_action', type: 'string', example: 'تنظيف الممرات'),
                                    new OA\Property(property: 'deadline', type: 'string', format: 'date', example: '2026-02-28'),
                                    new OA\Property(property: 'status_text', type: 'string', example: 'قيد الانتظار'),
                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مدير فرع أو ليس له فرع'
            ),
        ]
    )]
    public function branchManagerReport(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'branch_manager' || !$user->branch_id) {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }

        $branchId = $user->branch_id;
        $branch = Branch::with(['company'])->find($branchId);

        if (!$branch) {
            return response()->json(['message' => 'الفرع غير موجود'], 404);
        }

        $year = $request->input('year', now()->year);
        $month = $request->input('month') ? (int) $request->input('month') : null;

        // إحصائيات الفرع
        $totalVisits = Visit::where('branch_id', $branchId)->count();
        $avgScore = Visit::where('branch_id', $branchId)->whereNotNull('percentage')->avg('percentage') ?? 0;

        $visitsThisMonth = Visit::where('branch_id', $branchId)
            ->whereYear('created_at', $year)
            ->when($month, fn($q) => $q->whereMonth('created_at', $month))
            ->count();

        // آخر الزيارات (5)
        $recentVisits = Visit::where('branch_id', $branchId)
            ->with(['auditor', 'checklist'])
            ->latest('visit_date')
            ->limit(5)
            ->get();

        // التصحيحات المطلوبة
        $pendingCorrections = Answer::whereHas('visit', fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('corrective_action')
            ->whereNotIn('correction_status', ['completed', 'verified', 'waived'])
            ->with(['question'])
            ->latest()
            ->get()
            ->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'corrective_action' => $answer->corrective_action,
                    'deadline' => $answer->corrective_deadline?->format('Y-m-d'),
                    'status_text' => $answer->getCorrectionStatusText(),
                    'is_overdue' => $answer->isOverdue(),
                ];
            });

        // التذاكر المفتوحة للفرع
        $openTickets = Ticket::where('branch_id', $branchId)
            ->open()
            ->with(['creator'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'branch_info' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'compliance_score' => $branch->compliance_score,
                'category' => $branch->category,
                'last_visit_date' => $branch->last_visit_date?->format('Y-m-d'),
                'next_scheduled_visit' => $branch->next_scheduled_visit?->format('Y-m-d'),
            ],
            'statistics' => [
                'total_visits' => $totalVisits,
                'avg_score' => round($avgScore, 2),
                'visits_this_month' => $visitsThisMonth,
            ],
            'recent_visits' => VisitResource::collection($recentVisits),
            'pending_corrections' => $pendingCorrections,
            'open_tickets' => TicketResource::collection($openTickets),
        ]);
    }

    /**
     * تقرير المراقب (auditor)
     */
    #[OA\Get(
        path: '/api/v1/reports/auditor',
        operationId: 'auditorReport',
        summary: 'تقرير المراقب',
        description: 'إحصائيات الزيارات الخاصة بالمراقب، أداء المراقب، آخر التصحيحات التي تم التحقق منها',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'year',
                description: 'السنة المطلوبة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2026)
            ),
            new OA\Parameter(
                name: 'month',
                description: 'الشهر المطلوب (1-12)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تقرير المراقب',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'auditor_info',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'name', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'email', type: 'string', example: 'ahmed@example.com'),
                            ]
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_visits', type: 'integer', example: 35),
                                new OA\Property(property: 'completed_visits', type: 'integer', example: 20),
                                new OA\Property(property: 'scheduled_visits', type: 'integer', example: 8),
                                new OA\Property(property: 'in_progress_visits', type: 'integer', example: 2),
                                new OA\Property(property: 'avg_score', type: 'number', format: 'float', example: 79.4),
                            ]
                        ),
                        new OA\Property(
                            property: 'visits_by_month',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'month', type: 'string', example: '2026-02'),
                                    new OA\Property(property: 'count', type: 'integer', example: 5),
                                    new OA\Property(property: 'avg_percentage', type: 'number', format: 'float', example: 82.0),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'recent_verified_corrections',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'branch', type: 'string', example: 'الفرع الرئيسي'),
                                    new OA\Property(property: 'question', type: 'string', example: 'هل تم تنظيف الممرات؟'),
                                    new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', example: '2026-02-10 14:30'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مراقب'
            ),
        ]
    )]
    public function auditorReport(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'auditor') {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }

        $auditorId = $user->id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month') ? (int) $request->input('month') : null;

        // إحصائيات الزيارات
        $totalVisits = Visit::where('auditor_id', $auditorId)->count();
        $completedVisits = Visit::where('auditor_id', $auditorId)->where('status', 'completed')->count();
        $scheduledVisits = Visit::where('auditor_id', $auditorId)
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->count();
        $inProgressVisits = Visit::where('auditor_id', $auditorId)->where('status', 'in_progress')->count();

        $avgScore = Visit::where('auditor_id', $auditorId)
            ->whereNotNull('percentage')
            ->avg('percentage') ?? 0;

        // توزيع الزيارات شهريًا للسنة المحددة
        $visitsByMonth = Visit::where('auditor_id', $auditorId)
            ->whereYear('created_at', $year)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('count(*) as count'),
                DB::raw('avg(percentage) as avg_percentage')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // التصحيحات التي تم التحقق منها مؤخراً
        $verifiedCorrections = Answer::where('verified_by', $auditorId)
            ->with(['question', 'visit.branch'])
            ->latest('verified_at')
            ->limit(5)
            ->get()
            ->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'branch' => $answer->visit->branch->name ?? 'غير محدد',
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'verified_at' => $answer->verified_at?->format('Y-m-d H:i'),
                ];
            });

        // الزيارات القادمة (خلال الأسبوع)
        $upcomingVisits = Visit::where('auditor_id', $auditorId)
            ->where('scheduled_date', '>', now()->toDateString())
            ->where('scheduled_date', '<=', now()->addDays(7)->toDateString())
            ->with(['branch', 'checklist'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return response()->json([
            'auditor_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'statistics' => [
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'scheduled_visits' => $scheduledVisits,
                'in_progress_visits' => $inProgressVisits,
                'avg_score' => round($avgScore, 2),
            ],
            'visits_by_month' => $visitsByMonth,
            'recent_verified_corrections' => $verifiedCorrections,
            'upcoming_visits' => VisitResource::collection($upcomingVisits),
        ]);
    }
}
