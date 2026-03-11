<?php

namespace App\Services\V1\AI;

use App\Models\V1\Visit;
use App\Models\V1\AiSuggestion;
use App\Models\V1\Branch;
use App\Models\V1\Statistic;
use App\Services\V1\OpenAiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeepAnalysisService
{
    protected $openAiService;
    protected $costPerAnalysis = 0;
    protected $tokensUsed = 0;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * تحليل عميق لزيارة محددة
     */
    public function analyzeVisit(Visit $visit, array $options = []): array
    {
        $startTime = microtime(true);

        // 1. جمع كل البيانات المطلوبة
        $data = $this->gatherVisitData($visit);

        // 2. بناء prompts متعددة للتحليل الشامل
        $analyses = [
            'root_cause' => $this->analyzeRootCauses($data),
            'patterns' => $this->analyzePatterns($data),
            'predictive' => $this->predictiveAnalysis($data),
            'comparative' => $this->comparativeAnalysis($visit),
            'recommendations' => $this->generateDetailedRecommendations($data),
            'risk_assessment' => $this->assessRisks($data),
            'action_plan' => $this->createActionPlan($data),
        ];

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        // 3. حفظ نتائج التحليل
        $analysis = $this->saveAnalysisResults($visit, $analyses, [
            'processing_time' => $processingTime,
            'tokens_used' => $this->tokensUsed,
            'cost' => $this->costPerAnalysis,
        ]);

        return [
            'id' => $analysis->id,
            'visit_id' => $visit->id,
            'summary' => $this->generateExecutiveSummary($analyses),
            'analyses' => $analyses,
            'metadata' => [
                'processing_time_ms' => $processingTime,
                'tokens_used' => $this->tokensUsed,
                'estimated_cost_usd' => round($this->costPerAnalysis, 4),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * جمع بيانات الزيارة بشكل كامل
     */
    protected function gatherVisitData(Visit $visit): array
    {
        // جلب جميع الإجابات مع الأسئلة
        $answers = $visit->answers()
            ->with(['question.criteria', 'question.checklist'])
            ->get();

        // جلب الإحصائيات السابقة للفرع
        $previousStats = Statistic::where('branch_id', $visit->branch_id)
            ->where('record_date', '<', $visit->visit_date)
            ->orderBy('record_date', 'desc')
            ->limit(10)
            ->get();

        // جلب تحليلات سابقة إن وجدت
        $previousAnalyses = AiSuggestion::where('branch_id', $visit->branch_id)
            ->where('created_at', '<', $visit->visit_date)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // تحليل الإجابات حسب المعايير
        $criteriaAnalysis = [];
        foreach ($answers->groupBy('question.criteria_id') as $criteriaId => $criteriaAnswers) {
            $criteria = $criteriaAnswers->first()->question->criteria;
            $totalScore = $criteriaAnswers->sum('score');
            $totalMax = $criteriaAnswers->sum('max_score');
            $percentage = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0;

            $criteriaAnalysis[] = [
                'criteria_id' => $criteriaId,
                'name' => $criteria->name,
                'category' => $criteria->category,
                'weight' => $criteria->weight,
                'score' => $totalScore,
                'max_score' => $totalMax,
                'percentage' => round($percentage, 2),
                'questions' => $criteriaAnswers->map(function ($answer) {
                    return [
                        'id' => $answer->question_id,
                        'text' => $answer->question->question_text,
                        'type' => $answer->question->type,
                        'score' => $answer->score,
                        'max_score' => $answer->max_score,
                        'percentage' => $answer->max_score > 0 
                            ? round(($answer->score / $answer->max_score) * 100, 2)
                            : 0,
                        'answer' => $answer->answer_value,
                        'comment' => $answer->comment,
                        'is_critical' => $answer->question->is_critical,
                        'is_required' => $answer->question->is_required,
                    ];
                })->toArray(),
            ];
        }

        return [
            'visit' => [
                'id' => $visit->id,
                'date' => $visit->visit_date->format('Y-m-d'),
                'score' => round($visit->percentage, 2),
                'category' => $visit->category,
                'status' => $visit->status,
                'duration' => $visit->actual_duration ?? $visit->estimated_duration,
            ],
            'branch' => [
                'id' => $visit->branch->id,
                'name' => $visit->branch->name,
                'city' => $visit->branch->city,
                'category' => $visit->branch->category,
                'compliance_score' => $visit->branch->compliance_score,
                'total_visits' => $visit->branch->total_visits,
                'last_visit' => $visit->branch->last_visit_date?->format('Y-m-d'),
            ],
            'company' => [
                'id' => $visit->branch->company->id,
                'name' => $visit->branch->company->name,
                'industry' => $visit->branch->company->category,
            ],
            'checklist' => [
                'id' => $visit->checklist->id,
                'name' => $visit->checklist->name,
                'total_questions' => $visit->checklist->questions()->count(),
                'passing_score' => $visit->checklist->passing_score,
            ],
            'answers' => [
                'total' => $answers->count(),
                'answered' => $answers->count(),
                'total_score' => round($answers->sum('score'), 2),
                'max_possible' => $answers->sum('max_score'),
                'lost_points' => $answers->sum('max_score') - $answers->sum('score'),
                'critical_errors' => $answers->filter(function ($a) {
                    return $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1);
                })->count(),
            ],
            'criteria_analysis' => $criteriaAnalysis,
            'historical' => [
                'previous_stats' => $previousStats->map(function ($stat) {
                    return [
                        'date' => $stat->record_date->format('Y-m-d'),
                        'metric' => $stat->metric_name,
                        'value' => $stat->metric_value,
                        'trend' => $stat->trend_direction,
                    ];
                })->toArray(),
                'previous_suggestions' => $previousAnalyses->map(function ($suggestion) {
                    return [
                        'date' => $suggestion->created_at->format('Y-m-d'),
                        'metric' => $suggestion->metric_name,
                        'suggestion' => $suggestion->suggestion,
                        'applied' => $suggestion->is_applied,
                    ];
                })->toArray(),
            ],
        ];
    }

    /**
     * تحليل الأسباب الجذرية
     */
    protected function analyzeRootCauses(array $data): array
    {
        $prompt = $this->buildRootCausePrompt($data);

        $response = $this->callAI($prompt, 'root_cause');

        return [
            'primary_causes' => $response['primary_causes'] ?? [],
            'secondary_causes' => $response['secondary_causes'] ?? [],
            'contributing_factors' => $response['contributing_factors'] ?? [],
            'systemic_issues' => $response['systemic_issues'] ?? [],
            'summary' => $response['summary'] ?? 'لم يتم تحديد أسباب جذرية واضحة',
        ];
    }

    /**
     * تحليل الأنماط والتكرارات
     */
    protected function analyzePatterns(array $data): array
    {
        $prompt = $this->buildPatternsPrompt($data);

        $response = $this->callAI($prompt, 'patterns');

        return [
            'recurring_issues' => $response['recurring_issues'] ?? [],
            'emerging_patterns' => $response['emerging_patterns'] ?? [],
            'correlations' => $response['correlations'] ?? [],
            'temporal_patterns' => $response['temporal_patterns'] ?? [],
            'summary' => $response['summary'] ?? 'لم يتم اكتشاف أنماط واضحة',
        ];
    }

    /**
     * تحليل تنبؤي
     */
    protected function predictiveAnalysis(array $data): array
    {
        $prompt = $this->buildPredictivePrompt($data);

        $response = $this->callAI($prompt, 'predictive');

        return [
            'forecast_next_score' => $response['forecast_next_score'] ?? $data['visit']['score'],
            'confidence_interval' => $response['confidence_interval'] ?? [0, 100],
            'risk_factors' => $response['risk_factors'] ?? [],
            'improvement_potential' => $response['improvement_potential'] ?? 0,
            'critical_warnings' => $response['critical_warnings'] ?? [],
            'summary' => $response['summary'] ?? 'لا يمكن تقديم تحليل تنبؤي دقيق',
        ];
    }

    /**
     * تحليل مقارن مع فروع مماثلة
     */
    protected function comparativeAnalysis(Visit $visit): array
    {
        // البحث عن فروع مماثلة
        $similarBranches = Branch::where('company_id', $visit->branch->company_id)
            ->where('id', '!=', $visit->branch_id)
            ->where('category', $visit->branch->category)
            ->with(['visits' => function ($q) {
                $q->where('status', 'completed')
                  ->orderBy('visit_date', 'desc')
                  ->limit(1);
            }])
            ->limit(5)
            ->get();

        $comparisonData = [];
        foreach ($similarBranches as $branch) {
            $lastVisit = $branch->visits->first();
            if ($lastVisit) {
                $comparisonData[] = [
                    'branch_name' => $branch->name,
                    'score' => $lastVisit->percentage,
                    'date' => $lastVisit->visit_date->format('Y-m-d'),
                ];
            }
        }

        $prompt = $this->buildComparativePrompt($visit, $comparisonData);

        $response = $this->callAI($prompt, 'comparative');

        return [
            'peer_comparison' => $comparisonData,
            'ranking' => $response['ranking'] ?? 'متوسط',
            'strengths_vs_peers' => $response['strengths_vs_peers'] ?? [],
            'weaknesses_vs_peers' => $response['weaknesses_vs_peers'] ?? [],
            'best_practices' => $response['best_practices'] ?? [],
            'summary' => $response['summary'] ?? 'لا توجد بيانات كافية للمقارنة',
        ];
    }

    /**
     * توليد توصيات مفصلة
     */
    protected function generateDetailedRecommendations(array $data): array
    {
        $prompt = $this->buildRecommendationsPrompt($data);
        $response = $this->callAI($prompt, 'recommendations');

        // إضافة قيم افتراضية للعناصر المفقودة
        $immediateActions = array_map(function ($action) {
            return [
                'task' => $action['task'] ?? 'مهمة غير محددة',
                'estimated_impact' => $action['estimated_impact'] ?? 0,
                'priority' => 'high',
                'timeline' => 'خلال 24-48 ساعة',
                'responsible' => $action['responsible'] ?? 'مدير الفرع',
                'resources' => $action['resources'] ?? [],
            ];
        }, $response['immediate_actions'] ?? []);

        $shortTerm = array_map(function ($action) {
            return [
                'task' => $action['task'] ?? 'مهمة غير محددة',
                'estimated_impact' => $action['estimated_impact'] ?? 0,
                'priority' => 'medium',
                'timeline' => 'خلال أسبوع',
                'responsible' => $action['responsible'] ?? 'مدير الفرع',
                'resources' => $action['resources'] ?? [],
            ];
        }, $response['short_term'] ?? []);

        $longTerm = array_map(function ($action) {
            return [
                'task' => $action['task'] ?? 'مهمة غير محددة',
                'estimated_impact' => $action['estimated_impact'] ?? 0,
                'priority' => 'low',
                'timeline' => 'شهر - 3 أشهر',
                'responsible' => $action['responsible'] ?? 'مدير الفرع',
                'resources' => $action['resources'] ?? [],
            ];
        }, $response['long_term'] ?? []);

        $quickWins = array_map(function ($win) {
            return [
                'task' => $win['task'] ?? 'فرصة سريعة',
                'estimated_impact' => $win['estimated_impact'] ?? 0,
                'impact' => 'high',
                'effort' => 'low',
                'timeline' => 'خلال 24 ساعة',
                'responsible' => $win['responsible'] ?? 'مدير الفرع',
            ];
        }, $response['quick_wins'] ?? []);

        return [
            'immediate_actions' => $immediateActions,
            'short_term' => $shortTerm,
            'long_term' => $longTerm,
            'quick_wins' => $quickWins,
            'training_needs' => $response['training_needs'] ?? [],
            'resource_needs' => $response['resource_needs'] ?? [],
            'process_changes' => $response['process_changes'] ?? [],
        ];
    }

    /**
     * تقييم المخاطر
     */
    protected function assessRisks(array $data): array
    {
        $prompt = $this->buildRiskPrompt($data);

        $response = $this->callAI($prompt, 'risk');

        return [
            'critical_risks' => array_map(function ($risk) {
                return array_merge($risk, [
                    'level' => 'critical',
                    'mitigation_deadline' => 'فوري',
                ]);
            }, $response['critical_risks'] ?? []),
            
            'high_risks' => array_map(function ($risk) {
                return array_merge($risk, [
                    'level' => 'high',
                    'mitigation_deadline' => 'خلال 3 أيام',
                ]);
            }, $response['high_risks'] ?? []),
            
            'medium_risks' => array_map(function ($risk) {
                return array_merge($risk, [
                    'level' => 'medium',
                    'mitigation_deadline' => 'خلال أسبوعين',
                ]);
            }, $response['medium_risks'] ?? []),
            
            'compliance_risks' => $response['compliance_risks'] ?? [],
            'reputation_risks' => $response['reputation_risks'] ?? [],
            'financial_risks' => $response['financial_risks'] ?? [],
            'summary' => $response['summary'] ?? 'تقييم المخاطر غير متوفر',
        ];
    }

    /**
     * إنشاء خطة عمل متكاملة
     */
    protected function createActionPlan(array $data): array
    {
        $prompt = $this->buildActionPlanPrompt($data);
        $response = $this->callAI($prompt, 'action_plan');

        $plan = [];

        // تجميع المهام حسب الأولوية مع التحقق من وجود المفاتيح
        foreach (['high', 'medium', 'low'] as $priority) {
            $key = $priority . '_priority';
            if (isset($response[$key]) && is_array($response[$key])) {
                foreach ($response[$key] as $task) {
                    $plan[] = [
                        'task' => $task['task'] ?? 'مهمة غير محددة',
                        'priority' => $priority,
                        'responsible' => $task['responsible'] ?? 'مدير الفرع',
                        'timeline' => $task['timeline'] ?? $this->getDefaultTimeline($priority),
                        'resources' => $task['resources'] ?? [],
                        'success_metrics' => $task['success_metrics'] ?? [],
                        'estimated_impact' => $task['estimated_impact'] ?? 0,
                    ];
                }
            }
        }

        return [
            'tasks' => $plan,
            'milestones' => $response['milestones'] ?? [],
            'dependencies' => $response['dependencies'] ?? [],
            'estimated_timeline' => $response['estimated_timeline'] ?? '4-6 أسابيع',
            'success_criteria' => $response['success_criteria'] ?? [],
            'review_points' => $response['review_points'] ?? [],
        ];
    }

    /**
     * توليد ملخص تنفيذي
     */
    protected function generateExecutiveSummary(array $analyses): string
    {
        $summary = "تحليل متقدم للزيارة:\n\n";

        // الأسباب الجذرية
        if (!empty($analyses['root_cause']['summary'])) {
            $summary .= "🔍 الأسباب الجذرية: " . $analyses['root_cause']['summary'] . "\n\n";
        }

        // المخاطر الحرجة
        if (!empty($analyses['risk_assessment']['critical_risks'])) {
            $criticalCount = count($analyses['risk_assessment']['critical_risks']);
            $summary .= "⚠️ مخاطر حرجة تم اكتشافها: {$criticalCount}\n";
        }

        // توقعات التحسن
        if (!empty($analyses['predictive']['improvement_potential'])) {
            $potential = $analyses['predictive']['improvement_potential'];
            $summary .= "📈 إمكانية التحسن المتوقعة: +{$potential} نقطة\n\n";
        }

        // فرص سريعة
        if (!empty($analyses['recommendations']['quick_wins'])) {
            $quickWins = count($analyses['recommendations']['quick_wins']);
            $summary .= "⚡ فرص سريعة (تأثير عالي/جهد منخفض): {$quickWins}\n\n";
        }

        // توصيات رئيسية
        $summary .= "📋 أبرز التوصيات:\n";
        $immediateActions = $analyses['recommendations']['immediate_actions'] ?? [];
        foreach (array_slice($immediateActions, 0, 3) as $rec) {
            $task = $rec['task'] ?? 'توصية غير محددة';
            $impact = isset($rec['estimated_impact']) ? " (+{$rec['estimated_impact']} نقاط)" : '';
            $summary .= "  • {$task}{$impact}\n";
        }

        return $summary;
    }

    /**
     * بناء prompt لتحليل الأسباب الجذرية
     */
    protected function buildRootCausePrompt(array $data): string
    {
        return "
قم بتحليل عميق للأسباب الجذرية لنتائج التقييم التالية:

معلومات الزيارة:
- التاريخ: {$data['visit']['date']}
- النتيجة الإجمالية: {$data['visit']['score']}%
- التصنيف: {$data['visit']['category']}

تفاصيل النتائج:
- إجمالي النقاط المفقودة: {$data['answers']['lost_points']}
- الأخطاء الحرجة: {$data['answers']['critical_errors']}

تحليل المعايير:
" . json_encode($data['criteria_analysis'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب:
1. تحديد الأسباب الجذرية الرئيسية (3-5 أسباب)
2. تحديد الأسباب الثانوية المساهمة
3. تحليل العوامل المساعدة (بيئية، بشرية، إجرائية)
4. اكتشاف المشاكل النظامية إن وجدت

أجب بصيغة JSON تحتوي على:
- primary_causes: مصفوفة من الأسباب الرئيسية (كل سبب له وصف وتأثير)
- secondary_causes: مصفوفة من الأسباب الثانوية
- contributing_factors: مصفوفة من العوامل المساعدة
- systemic_issues: مصفوفة من المشاكل النظامية
- summary: ملخص قصير (جملتين)
";
    }

    /**
     * بناء prompt لتحليل الأنماط
     */
    protected function buildPatternsPrompt(array $data): string
    {
        return "
قم بتحليل الأنماط والتكرارات في بيانات التقييم:

البيانات التاريخية:
" . json_encode($data['historical']['previous_stats'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

الاقتراحات السابقة:
" . json_encode($data['historical']['previous_suggestions'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب:
1. تحديد المشاكل المتكررة عبر الزمن
2. اكتشاف أنماط جديدة ناشئة
3. تحليل الارتباطات بين المعايير المختلفة
4. دراسة الأنماط الزمنية (أوقات معينة، أيام محددة)

أجب بصيغة JSON تحتوي على:
- recurring_issues: مصفوفة من المشاكل المتكررة
- emerging_patterns: مصفوفة من الأنماط الجديدة
- correlations: مصفوفة من الارتباطات المكتشفة
- temporal_patterns: مصفوفة من الأنماط الزمنية
- summary: ملخص قصير
";
    }

    /**
     * بناء prompt للتحليل التنبؤي
     */
    protected function buildPredictivePrompt(array $data): string
    {
        return "
قم بتحليل تنبؤي بناءً على البيانات المتاحة:

النتيجة الحالية: {$data['visit']['score']}%
الاتجاه التاريخي: 
" . json_encode($data['historical']['previous_stats'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب:
1. توقع النتيجة في الزيارة القادمة
2. تحديد فترة الثقة (confidence interval)
3. تحليل عوامل الخطر التي قد تؤثر سلباً
4. تقدير إمكانية التحسين القصوى
5. تحديد تحذيرات حرجة مبكرة

أجب بصيغة JSON تحتوي على:
- forecast_next_score: رقم (النسبة المتوقعة)
- confidence_interval: مصفوفة [أدنى, أعلى]
- risk_factors: مصفوفة من عوامل الخطر
- improvement_potential: رقم (النقاط الممكن رفعها)
- critical_warnings: مصفوفة من التحذيرات
- summary: ملخص قصير
";
    }

    /**
     * بناء prompt للتحليل المقارن
     */
    protected function buildComparativePrompt(Visit $visit, array $comparisonData): string
    {
        return "
قم بتحليل مقارن بين هذا الفرع والفروع المماثلة:

الفرع الحالي:
- الاسم: {$visit->branch->name}
- النتيجة: {$visit->percentage}%
- التصنيف: {$visit->branch->category}

فروع مماثلة:
" . json_encode($comparisonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب:
1. تحديد ترتيب الفرع مقارنة بالأقران
2. استخراج نقاط القوة التي تميزه عن الآخرين
3. استخراج نقاط الضعف التي يقل فيها عن الآخرين
4. اقتراح أفضل الممارسات من الفروع المتميزة

أجب بصيغة JSON تحتوي على:
- ranking: نص (ممتاز، فوق المتوسط، متوسط، تحت المتوسط، ضعيف)
- strengths_vs_peers: مصفوفة من نقاط القوة
- weaknesses_vs_peers: مصفوفة من نقاط الضعف
- best_practices: مصفوفة من الممارسات المقترحة
- summary: ملخص قصير
";
    }

    /**
     * بناء prompt للتوصيات المفصلة
     */
    protected function buildRecommendationsPrompt(array $data): string
    {
        $weakCriteria = array_filter($data['criteria_analysis'], function ($c) {
            return $c['percentage'] < 70;
        });

        return "
قم بتوليد توصيات مفصلة وعملية بناءً على تحليل المعايير الضعيفة:

المعايير الضعيفة (تحتاج تحسين):
" . json_encode($weakCriteria, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب توليد توصيات في الفئات التالية:

1. إجراءات فورية (خلال 24-48 ساعة)
2. إجراءات قصيرة المدى (خلال أسبوع)
3. إجراءات طويلة المدى (شهر - 3 أشهر)
4. فرص سريعة (تأثير عالي، جهد منخفض)
5. احتياجات تدريبية
6. احتياجات موارد ومعدات
7. تغييرات إجرائية

لكل توصية، حدد:
- المهمة المحددة
- الشخص المسؤول
- التأثير المتوقع بالنقاط
- الموارد المطلوبة

أجب بصيغة JSON تحتوي على:
- immediate_actions: مصفوفة
- short_term: مصفوفة
- long_term: مصفوفة
- quick_wins: مصفوفة
- training_needs: مصفوفة
- resource_needs: مصفوفة
- process_changes: مصفوفة
";
    }

    /**
     * بناء prompt لتقييم المخاطر
     */
    protected function buildRiskPrompt(array $data): string
    {
        return "
قم بتقييم المخاطر بناءً على تحليل التقييم:

نتائج التقييم:
- النتيجة: {$data['visit']['score']}%
- الأخطاء الحرجة: {$data['answers']['critical_errors']}
- النقاط المفقودة: {$data['answers']['lost_points']}

المعايير الضعيفة:
" . json_encode(array_filter($data['criteria_analysis'], fn($c) => $c['percentage'] < 60), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

المطلوب تصنيف المخاطر حسب المستوى:

1. مخاطر حرجة (تأثير فوري وكبير)
2. مخاطر عالية (تأثير كبير لكن غير فوري)
3. مخاطر متوسطة (تأثير محدود)
4. مخاطر امتثال (قانونية/تنظيمية)
5. مخاطر سمعة
6. مخاطر مالية

لكل خطر، حدد:
- وصف الخطر
- مستوى التأثير
- احتمال الحدوث
- إجراءات التخفيف المقترحة
- المهلة الزمنية للتعامل معه

أجب بصيغة JSON تحتوي على:
- critical_risks: مصفوفة
- high_risks: مصفوفة
- medium_risks: مصفوفة
- compliance_risks: مصفوفة
- reputation_risks: مصفوفة
- financial_risks: مصفوفة
- summary: ملخص قصير
";
    }

    /**
     * بناء prompt لخطة العمل
     */
    protected function buildActionPlanPrompt(array $data): string
    {
        return "
قم بإنشاء خطة عمل متكاملة بناءً على جميع التحليلات السابقة:

النتيجة الحالية: {$data['visit']['score']}%
الأخطاء الحرجة: {$data['answers']['critical_errors']}

المطلوب إنشاء خطة عمل تحتوي على:

1. مهام ذات أولوية عالية (يجب تنفيذها فوراً)
2. مهام ذات أولوية متوسطة (خلال أسبوعين)
3. مهام ذات أولوية منخفضة (شهر فأكثر)
4. مراحل رئيسية (milestones)
5. تبعيات بين المهام
6. معايير النجاح
7. نقاط مراجعة دورية

لكل مهمة، حدد:
- المهمة
- الأولوية
- الشخص المسؤول
- الجدول الزمني
- الموارد المطلوبة
- معايير قياس النجاح
- التأثير المتوقع بالنقاط

أجب بصيغة JSON تحتوي على:
- high_priority: مصفوفة
- medium_priority: مصفوفة
- low_priority: مصفوفة
- milestones: مصفوفة
- dependencies: مصفوفة
- success_criteria: مصفوفة
- review_points: مصفوفة
- estimated_timeline: نص
";
    }

    /**
     * استدعاء الذكاء الاصطناعي
     */
    protected function callAI(string $prompt, string $type): array
    {
        try {
            $response = $this->openAiService->deepAnalysis($prompt, $type);

            // تحديث الإحصائيات
            $this->tokensUsed += $response['tokens'] ?? 0;
            $this->costPerAnalysis += $this->calculateCost($response['tokens'] ?? 0);

            return json_decode($response['text'], true) ?? [];
        } catch (\Exception $e) {
            Log::error("Deep AI analysis failed for type: {$type}", [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => true,
                'summary' => "فشل التحليل: " . $e->getMessage(),
            ];
        }
    }

    /**
     * حساب التكلفة
     */
    protected function calculateCost(int $tokens): float
    {
        // سعر تقريبي: $0.03 لكل 1000 token
        return ($tokens / 1000) * 0.03;
    }

    /**
     * الحصول على الجدول الزمني الافتراضي حسب الأولوية
     */
    protected function getDefaultTimeline(string $priority): string
    {
        return match($priority) {
            'high' => 'خلال 48 ساعة',
            'medium' => 'خلال أسبوعين',
            'low' => 'خلال شهر',
            default => 'غير محدد',
        };
    }

    /**
     * حفظ نتائج التحليل
     */
    protected function saveAnalysisResults(Visit $visit, array $analyses, array $metadata): AiSuggestion
    {
        return AiSuggestion::create([
            'statistic_id' => null,
            'metric_name' => 'deep_analysis',
            'record_date' => now()->toDateString(),
            'period_type' => 'daily',
            'company_id' => $visit->branch->company_id,
            'branch_id' => $visit->branch_id,
            'current_value' => $visit->percentage,
            'previous_value' => null,
            'percentage_change' => null,
            'trend_direction' => 'stable',
            'severity' => $visit->percentage < 60 ? 'critical' : ($visit->percentage < 75 ? 'warning' : 'info'),
            'status' => $this->determineStatus($visit->percentage),
            'suggestion' => $analyses['recommendations']['summary'] ?? 'تحليل متقدم',
            'summary' => $analyses['summary'] ?? 'تحليل شامل للزيارة',
            'confidence' => 90,
            'ai_provider' => 'openai',
            'ai_model' => setting('deep_analysis_model', 'gpt-3.5-turbo', 'ai'),
            'tokens_used' => $metadata['tokens_used'],
            'processing_time' => $metadata['processing_time'],
            'metadata' => [
                'analyses' => $analyses,
                'processing_stats' => $metadata,
            ],
        ]);
    }

    /**
     * تحديد الحالة من النسبة
     */
    protected function determineStatus(float $percentage): string
    {
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 80) return 'good';
        if ($percentage >= 70) return 'average';
        if ($percentage >= 60) return 'poor';
        return 'critical';
    }
}