<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BranchResource;
use App\Http\Resources\V1\CompanyResource;
use App\Http\Resources\V1\VisitResource;
use App\Http\Resources\V1\TicketResource;
use App\Models\V1\Company;
use App\Models\V1\Branch;
use App\Models\V1\Visit;
use App\Models\V1\User;
use App\Models\V1\Ticket;
use App\Models\V1\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard/super-admin',
        operationId: 'superAdminDashboard',
        summary: 'لوحة تحكم مدير النظام',
        description: 'إحصائيات عامة عن النظام، أحدث الشركات والزيارات، توزيع الزيارات، أفضل الشركات',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'بيانات لوحة التحكم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_companies', type: 'integer', example: 25),
                                new OA\Property(property: 'total_branches', type: 'integer', example: 120),
                                new OA\Property(property: 'total_users', type: 'integer', example: 300),
                                new OA\Property(property: 'total_auditors', type: 'integer', example: 45),
                                new OA\Property(property: 'total_visits', type: 'integer', example: 850),
                                new OA\Property(property: 'completed_visits', type: 'integer', example: 600),
                                new OA\Property(property: 'scheduled_visits', type: 'integer', example: 150),
                                new OA\Property(property: 'in_progress_visits', type: 'integer', example: 50),
                                new OA\Property(property: 'avg_percentage', type: 'number', format: 'float', example: 78.5),
                            ]
                        ),
                        new OA\Property(
                            property: 'tickets',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 120),
                                new OA\Property(property: 'open', type: 'integer', example: 35),
                                new OA\Property(property: 'overdue', type: 'integer', example: 8),
                            ]
                        ),
                        new OA\Property(
                            property: 'category_distribution',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'integer', example: 15)
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
                response: 401,
                description: 'غير مصرح به'
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مدير نظام'
            ),
        ]
    )]
    public function superAdminDashboard()
    {
        // التحقق من الدور (يمكن استخدام Gate أو Policy)
        if (auth()->user()->role !== 'super_admin') {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }

        // إحصائيات عامة
        $totalCompanies = Company::count();
        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $totalAuditors = User::where('role', 'auditor')->count();
        $totalVisits = Visit::count();
        $completedVisits = Visit::where('status', 'completed')->count();
        $scheduledVisits = Visit::where('status', 'scheduled')->count();
        $inProgressVisits = Visit::where('status', 'in_progress')->count();
        
        // متوسط الأداء العام
        $avgPercentage = Visit::whereNotNull('percentage')->avg('percentage') ?? 0;
        
        // أحدث الشركات
        $latestCompanies = Company::latest()->limit(5)->get();
        
        // أحدث الزيارات
        $latestVisits = Visit::with(['branch', 'auditor'])->latest()->limit(5)->get();
        
        // توزيع الفئات
        $categoryDistribution = Visit::whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');
        
        // إحصائيات التذاكر
        $totalTickets = Ticket::count();
        $openTickets = Ticket::open()->count();
        $overdueTickets = Ticket::overdue()->count();
        
        // أفضل الشركات حسب متوسط النسبة
        $topCompanies = Company::withAvg('visits as avg_percentage', 'percentage')
            ->orderByDesc('avg_percentage')
            ->limit(5)
            ->get(['id', 'name', 'avg_percentage']);
        
        return response()->json([
            'statistics' => [
                'total_companies' => $totalCompanies,
                'total_branches' => $totalBranches,
                'total_users' => $totalUsers,
                'total_auditors' => $totalAuditors,
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'scheduled_visits' => $scheduledVisits,
                'in_progress_visits' => $inProgressVisits,
                'avg_percentage' => round($avgPercentage, 2),
            ],
            'tickets' => [
                'total' => $totalTickets,
                'open' => $openTickets,
                'overdue' => $overdueTickets,
            ],
            'category_distribution' => $categoryDistribution,
            'latest_companies' => CompanyResource::collection($latestCompanies),
            'latest_visits' => VisitResource::collection($latestVisits),
            'top_companies' => $topCompanies,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/dashboard/company-admin',
        operationId: 'companyAdminDashboard',
        summary: 'لوحة تحكم مدير الشركة',
        description: 'إحصائيات خاصة بشركة المستخدم الحالي، أداء الفروع، التصحيحات المعلقة، التذاكر المفتوحة',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'بيانات لوحة التحكم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
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
                            property: 'branches_category',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'integer', example: 2)
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
                response: 401,
                description: 'غير مصرح به'
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مدير شركة أو ليس له شركة'
            ),
        ]
    )]
    /**
     * حالة الاشتراك (العقد/المتطلبات: عرض مدة الاشتراك المتبقية لكل منشأة وفرع)
     */
    private function subscriptionInfo($company): ?array
    {
        if (!$company) return null;
        return [
            'status' => $company->subscription_status,
            'start' => $company->subscription_start?->format('Y-m-d'),
            'end' => $company->subscription_end?->format('Y-m-d'),
            'remaining_days' => $company->getRemainingSubscriptionDays(),
            'auto_renew' => (bool) $company->auto_renew,
        ];
    }

    public function companyAdminDashboard(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'company_admin' || !$user->company_id) {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }
        
        $companyId = $user->company_id;
        
        // إحصائيات الشركة
        $branchesCount = Branch::where('company_id', $companyId)->count();
        $auditorsCount = User::where('company_id', $companyId)->where('role', 'auditor')->count();
        $branchManagersCount = User::where('company_id', $companyId)->where('role', 'branch_manager')->count();
        
        $visitsQuery = Visit::whereHas('branch', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
        
        $totalVisits = (clone $visitsQuery)->count();
        $completedVisits = (clone $visitsQuery)->where('status', 'completed')->count();
        $avgCompanyScore = (clone $visitsQuery)->whereNotNull('percentage')->avg('percentage') ?? 0;
        
        // توزيع الفئات للفروع
        $branchesCategory = Branch::where('company_id', $companyId)
            ->whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');
        
        // أداء الفروع
        $branchesPerformance = Branch::where('company_id', $companyId)
            ->with(['visits' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->get()
            ->map(function ($branch) {
                $lastVisit = $branch->visits->first();
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'last_visit_percentage' => $lastVisit?->percentage,
                    'category' => $branch->category,
                    'total_visits' => $branch->total_visits,
                ];
            });
        
        // أحدث الزيارات
        $latestVisits = (clone $visitsQuery)
            ->with(['branch', 'auditor'])
            ->latest()
            ->limit(5)
            ->get();
        
        // التصحيحات المعلقة
        $pendingCorrections = Answer::whereHas('visit.branch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('correction_status', 'pending')
            ->with(['question', 'visit.branch'])
            ->latest()
            ->limit(10)
            ->get();
        
        // التذاكر المفتوحة للشركة
        $openTickets = Ticket::where('company_id', $companyId)
            ->open()
            ->with(['creator', 'branch'])
            ->latest()
            ->limit(5)
            ->get();
        
        return response()->json([
            'subscription' => $this->subscriptionInfo(\App\Models\V1\Company::find($companyId)),
            'statistics' => [
                'branches_count' => $branchesCount,
                'auditors_count' => $auditorsCount,
                'branch_managers_count' => $branchManagersCount,
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'avg_company_score' => round($avgCompanyScore, 2),
            ],
            'branches_category' => $branchesCategory,
            'branches_performance' => $branchesPerformance,
            'latest_visits' => VisitResource::collection($latestVisits),
            'pending_corrections' => $pendingCorrections->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'branch' => $answer->visit->branch->name,
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'deadline' => $answer->corrective_deadline?->format('Y-m-d'),
                    'is_overdue' => $answer->isOverdue(),
                ];
            }),
            'open_tickets' => TicketResource::collection($openTickets),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/dashboard/branch-manager',
        operationId: 'branchManagerDashboard',
        summary: 'لوحة تحكم مدير الفرع',
        description: 'إحصائيات خاصة بالفرع، التصحيحات المطلوبة، التذاكر المفتوحة، آخر الزيارات',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'بيانات لوحة التحكم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'branch',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'الفرع الرئيسي'),
                                new OA\Property(property: 'company', type: 'string', example: 'شركة التقنية'),
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
                                new OA\Property(property: 'last_visit_score', type: 'number', format: 'float', example: 85.0),
                                new OA\Property(property: 'next_visit_date', type: 'string', format: 'date', example: '2026-03-01'),
                                new OA\Property(property: 'avg_score', type: 'number', format: 'float', example: 78.3),
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
                                    new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'status_text', type: 'string', example: 'قيد الانتظار'),
                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                ]
                            )
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
                description: 'ممنوع الوصول - المستخدم ليس مدير فرع أو ليس له فرع'
            ),
        ]
    )]
    public function branchManagerDashboard(Request $request)
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
        
        // إحصائيات الفرع
        $totalVisits = Visit::where('branch_id', $branchId)->count();
        $lastVisit = Visit::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->latest('visit_date')
            ->first();
        $nextVisit = Visit::where('branch_id', $branchId)
            ->whereIn('status', ['scheduled'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->first();
        
        $avgScore = Visit::where('branch_id', $branchId)
            ->whereNotNull('percentage')
            ->avg('percentage') ?? 0;
        
        // التصحيحات المطلوبة
        $pendingCorrections = Answer::whereHas('visit', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereNotNull('corrective_action')
            ->whereNotIn('correction_status', ['completed', 'verified', 'waived'])
            ->with(['question', 'visit'])
            ->latest()
            ->get();
        
        // التذاكر المفتوحة للفرع
        $openTickets = Ticket::where('branch_id', $branchId)
            ->open()
            ->with(['creator'])
            ->latest()
            ->limit(5)
            ->get();
        
        // أحدث الزيارات
        $recentVisits = Visit::where('branch_id', $branchId)
            ->with(['auditor', 'checklist'])
            ->latest('scheduled_date')
            ->limit(5)
            ->get();

        $auditor = $lastVisit?->auditor;

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'company' => $branch->company->name ?? null,
                'compliance_score' => $branch->compliance_score,
                'category' => $branch->category,
                'last_visit_date' => $branch->last_visit_date?->format('Y-m-d'),
                'next_scheduled_visit' => $branch->next_scheduled_visit?->format('Y-m-d'),
            ],
            'subscription' => $this->subscriptionInfo($branch->company),
            'statistics' => [
                'total_visits' => $totalVisits,
                'last_visit_score' => $lastVisit?->percentage,
                'next_visit_date' => $nextVisit?->scheduled_date,
                'avg_score' => round($avgScore, 2),
            ],
            'pending_corrections' => $pendingCorrections->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'corrective_action' => $answer->corrective_action,
                    'deadline' => $answer->corrective_deadline?->format('Y-m-d'),
                    'status' => $answer->correction_status,
                    'status_text' => $answer->getCorrectionStatusText(),
                    'is_overdue' => $answer->isOverdue(),
                ];
            }),
            'open_tickets' => TicketResource::collection($openTickets),
            'recent_visits' => VisitResource::collection($recentVisits),
            'auditor' => [
                'name' => $auditor?->name,
                'email' => $auditor?->email,
                'phone' => $auditor?->phone,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/dashboard/auditor',
        operationId: 'auditorDashboard',
        summary: 'لوحة تحكم المراقب',
        description: 'إحصائيات الزيارات الخاصة بالمراقب، زيارات اليوم والزيارات القادمة، آخر التصحيحات التي تم التحقق منها',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'بيانات لوحة التحكم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
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
                response: 401,
                description: 'غير مصرح به'
            ),
            new OA\Response(
                response: 403,
                description: 'ممنوع الوصول - المستخدم ليس مراقب'
            ),
        ]
    )]
    public function auditorDashboard(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role !== 'auditor') {
            return response()->json(['message' => 'غير مصرح بهذه الصفحة'], 403);
        }
        
        $auditorId = $user->id;
        
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
        
        // زيارات اليوم
        $todayVisits = Visit::where('auditor_id', $auditorId)
            ->where('scheduled_date', now()->toDateString())
            ->with(['branch', 'checklist'])
            ->orderBy('scheduled_time')
            ->get();
        
        // الزيارات القادمة (الأسبوع)
        $upcomingVisits = Visit::where('auditor_id', $auditorId)
            ->where('scheduled_date', '>', now()->toDateString())
            ->where('scheduled_date', '<=', now()->addDays(7)->toDateString())
            ->with(['branch', 'checklist'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();
        
        // أحدث الزيارات المنجزة
        $recentCompleted = Visit::where('auditor_id', $auditorId)
            ->where('status', 'completed')
            ->with(['branch', 'checklist'])
            ->latest('completed_at')
            ->limit(5)
            ->get();
        
        // التصحيحات التي تم التحقق منها مؤخراً
        $verifiedCorrections = Answer::where('verified_by', $auditorId)
            ->with(['question', 'visit.branch'])
            ->latest('verified_at')
            ->limit(5)
            ->get();
        
        return response()->json([
            'statistics' => [
                'total_visits' => $totalVisits,
                'completed_visits' => $completedVisits,
                'scheduled_visits' => $scheduledVisits,
                'in_progress_visits' => $inProgressVisits,
                'avg_score' => round($avgScore, 2),
            ],
            'today_visits' => VisitResource::collection($todayVisits),
            'upcoming_visits' => VisitResource::collection($upcomingVisits),
            'recent_completed_visits' => VisitResource::collection($recentCompleted),
            'recent_verified_corrections' => $verifiedCorrections->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'branch' => $answer->visit->branch->name ?? 'غير محدد',
                    'question' => $answer->question->question_text ?? 'غير محدد',
                    'verified_at' => $answer->verified_at?->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }
}