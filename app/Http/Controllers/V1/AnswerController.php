<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreAnswerRequest;
use App\Http\Requests\V1\UpdateAnswerRequest;
use App\Http\Requests\V1\BulkAnswerRequest;
use App\Http\Resources\V1\AnswerResource;
use App\Models\V1\Answer;
use App\Models\V1\Visit;
use App\Models\V1\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AnswerController extends Controller
{
    /*
    #[OA\Get(
        path: '/api/v1/visits/{visit}/answers',
        operationId: 'getVisitAnswers',
        summary: 'عرض إجابات زيارة',
        description: 'استرجاع جميع إجابات زيارة معينة مع إمكانية الفلترة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'correction_status',
                description: 'فلتر بحالة التصحيح',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['pending', 'in_progress', 'completed', 'verified', 'waived']
                )
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'question,attachments')
            ),
            new OA\Parameter(
                name: 'overdue_only',
                description: 'عرض التصحيحات المتأخرة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة إجابات الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'visit_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'answer_value', type: 'string', example: 'نعم'),
                                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                                    new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                    new OA\Property(property: 'score_percentage', type: 'number', format: 'float', example: 100.0),
                                    new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_correct_text', type: 'string', example: 'صحيح'),
                                    new OA\Property(property: 'comment', type: 'string', example: 'تعليق على الإجابة'),
                                    new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب'),
                                    new OA\Property(property: 'corrective_action', type: 'string', example: 'تحسين النظافة'),
                                    new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20'),
                                    new OA\Property(property: 'correction_status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'correction_status_text', type: 'string', example: 'قيد الانتظار'),
                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                    new OA\Property(property: 'corrected_at', type: 'string', format: 'date-time', example: null),
                                    new OA\Property(property: 'verified_by', type: 'integer', example: null),
                                    new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', example: null),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                    new OA\Property(
                                        property: 'question',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                            new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                            new OA\Property(property: 'type_text', type: 'string', example: 'نعم/لا'),
                                            new OA\Property(property: 'weight', type: 'integer', example: 5),
                                            new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                                            new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'attachments',
                                        type: 'array',
                                        nullable: true,
                                        items: new OA\Items(
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'original_name', type: 'string', example: 'صورة.jpg'),
                                                new OA\Property(property: 'file_url', type: 'string', example: 'http://example.com/storage/attachments/uuid.jpg'),
                                                new OA\Property(property: 'file_type', type: 'string', example: 'image'),
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_answers', type: 'integer', example: 15),
                                new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 135.5),
                                new OA\Property(property: 'max_possible_score', type: 'integer', example: 150),
                                new OA\Property(property: 'average_score', type: 'number', format: 'float', example: 9.03),
                                new OA\Property(property: 'corrections_pending', type: 'integer', example: 3),
                                new OA\Property(property: 'corrections_overdue', type: 'integer', example: 1),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function index(Visit $visit, Request $request): JsonResponse
    {
        $query = $visit->answers();
        
        if ($request->has('correction_status')) {
            $query->where('correction_status', $request->input('correction_status'));
        }
        
        if ($request->boolean('overdue_only')) {
            $query->where('corrective_deadline', '<', now()->toDateString())
                  ->whereNotIn('correction_status', ['completed', 'verified', 'waived']);
        }
        
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['question', 'attachments', 'verifier'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $answers = $query->with($with)->get();
        
        // إحصائيات
        $totalScore = $answers->sum('score');
        $maxPossibleScore = $answers->sum('max_score');
        $averageScore = $answers->count() > 0 ? $totalScore / $answers->count() : 0;
        
        $correctionsPending = $answers->where('correction_status', 'pending')->count();
        $correctionsOverdue = $answers->filter(function ($answer) {
            return $answer->isOverdue();
        })->count();
        
        return response()->json([
            'data' => AnswerResource::collection($answers),
            'statistics' => [
                'total_answers' => $answers->count(),
                'total_score' => $totalScore,
                'max_possible_score' => $maxPossibleScore,
                'average_score' => round($averageScore, 2),
                'corrections_pending' => $correctionsPending,
                'corrections_overdue' => $correctionsOverdue,
            ],
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/answers',
        operationId: 'createAnswer',
        summary: 'تسجيل إجابة جديدة',
        description: 'تسجيل إجابة لسؤال في زيارة معينة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['question_id', 'answer_value'],
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer', example: 1, description: 'معرف السؤال'),
                    new OA\Property(property: 'answer_value', type: 'string', example: 'نعم', description: 'قيمة الإجابة'),
                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0, description: 'الدرجة'),
                    new OA\Property(property: 'comment', type: 'string', example: 'تعليق على الإجابة', description: 'تعليق'),
                    new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب', description: 'تعليق المراقب'),
                    new OA\Property(property: 'corrective_action', type: 'string', example: 'الإجراء التصحيحي', description: 'الإجراء التصحيحي'),
                    new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20', description: 'موعد التصحيح'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم تسجيل الإجابة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'visit_id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                new OA\Property(property: 'answer_value', type: 'string', example: 'نعم'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                                new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                new OA\Property(property: 'is_correct_text', type: 'string', example: 'صحيح'),
                                new OA\Property(property: 'comment', type: 'string', example: 'تعليق على الإجابة'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب'),
                                new OA\Property(property: 'corrective_action', type: 'string', example: 'الإجراء التصحيحي'),
                                new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20'),
                                new OA\Property(property: 'correction_status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'correction_status_text', type: 'string', example: 'قيد الانتظار'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                new OA\Property(
                                    property: 'question',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                        new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                        new OA\Property(property: 'type_text', type: 'string', example: 'نعم/لا'),
                                        new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                        new OA\Property(property: 'weight', type: 'integer', example: 5),
                                    ]
                                ),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم تسجيل الإجابة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل قيمة الإجابة مطلوب')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة أو السؤال غير موجود',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function store(Visit $visit, StoreAnswerRequest $request): JsonResponse
    {
        $question = Question::findOrFail($request->input('question_id'));
        
        $answer = Answer::create([
            'visit_id' => $visit->id,
            'question_id' => $request->input('question_id'),
            'answer_value' => $request->input('answer_value'),
            'score' => $request->input('score', 0),
            'max_score' => $question->max_score,
            'is_correct' => $this->checkIfCorrect($question, $request->input('answer_value')),
            'comment' => $request->input('comment'),
            'auditor_comment' => $request->input('auditor_comment'),
            'corrective_action' => $request->input('corrective_action'),
            'corrective_deadline' => $request->input('corrective_deadline'),
            'correction_status' => $request->input('corrective_action') ? 'pending' : null,
        ]);
        
        $this->updateVisitStatistics($visit);
        
        return response()->json([
            'data' => new AnswerResource($answer->load(['question'])),
            'message' => 'تم تسجيل الإجابة بنجاح'
        ], 201);
    }
    
    #[OA\Get(
        path: '/api/v1/visits/{visit}/answers/{answer}',
        operationId: 'getAnswer',
        summary: 'عرض تفاصيل إجابة',
        description: 'استرجاع تفاصيل إجابة معينة مع علاقاتها',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'question,attachments,verifier')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل الإجابة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'visit_id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                new OA\Property(property: 'answer_value', type: 'string', example: 'نعم'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                                new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                new OA\Property(property: 'score_percentage', type: 'number', format: 'float', example: 100.0),
                                new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                new OA\Property(property: 'is_correct_text', type: 'string', example: 'صحيح'),
                                new OA\Property(property: 'comment', type: 'string', example: 'تعليق على الإجابة'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب'),
                                new OA\Property(property: 'corrective_action', type: 'string', example: 'تحسين النظافة'),
                                new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20'),
                                new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                new OA\Property(property: 'correction_status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'correction_status_text', type: 'string', example: 'قيد الانتظار'),
                                new OA\Property(property: 'corrected_at', type: 'string', format: 'date-time', example: null),
                                new OA\Property(property: 'verified_by', type: 'integer', example: null),
                                new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', example: null),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                new OA\Property(
                                    property: 'question',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                        new OA\Property(property: 'description', type: 'string', example: 'تقييم النظافة العامة للمكان'),
                                        new OA\Property(property: 'help_text', type: 'string', example: 'ركز على النظافة العامة وليس التفاصيل'),
                                        new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                        new OA\Property(property: 'type_text', type: 'string', example: 'نعم/لا'),
                                        new OA\Property(property: 'weight', type: 'integer', example: 5),
                                        new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                        new OA\Property(property: 'passing_score', type: 'integer', example: 6),
                                        new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                        new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                                        new OA\Property(property: 'has_attachment', type: 'boolean', example: false),
                                        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'visit',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                        new OA\Property(property: 'auditor_id', type: 'integer', example: 2),
                                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                        new OA\Property(property: 'status_text', type: 'string', example: 'مكتملة'),
                                        new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 85.5),
                                        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 85.5),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    nullable: true,
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'original_name', type: 'string', example: 'صورة.jpg'),
                                            new OA\Property(property: 'file_url', type: 'string', example: 'http://example.com/storage/attachments/uuid.jpg'),
                                            new OA\Property(property: 'file_type', type: 'string', example: 'image'),
                                            new OA\Property(property: 'file_size', type: 'integer', example: 2048576),
                                            new OA\Property(property: 'title', type: 'string', example: 'صورة للمكان'),
                                            new OA\Property(property: 'description', type: 'string', example: 'صورة توضح حالة النظافة'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:20:00Z'),
                                        ]
                                    )
                                ),
                                new OA\Property(
                                    property: 'verifier',
                                    type: 'object',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 3),
                                        new OA\Property(property: 'name', type: 'string', example: 'سالم علي'),
                                        new OA\Property(property: 'email', type: 'string', example: 'salem@example.com'),
                                        new OA\Property(property: 'role', type: 'string', example: 'مشرف'),
                                    ]
                                ),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة أو لا تنتمي إلى الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة لا تنتمي إلى هذه الزيارة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function show(Visit $visit, Answer $answer, Request $request): JsonResponse
    {
        if ($answer->visit_id !== $visit->id) {
            return response()->json([
                'message' => 'الإجابة لا تنتمي إلى هذه الزيارة'
            ], 404);
        }
        
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['question', 'attachments', 'verifier', 'visit'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $answer->load($with);
        
        return response()->json([
            'data' => new AnswerResource($answer)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/visits/{visit}/answers/{answer}',
        operationId: 'updateAnswer',
        summary: 'تحديث بيانات إجابة',
        description: 'تحديث بيانات إجابة موجودة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
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
                    new OA\Property(property: 'answer_value', type: 'string', example: 'لا', description: 'قيمة الإجابة'),
                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 0.0, description: 'الدرجة'),
                    new OA\Property(property: 'comment', type: 'string', example: 'تعليق محدث', description: 'تعليق'),
                    new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب المحدثة', description: 'تعليق المراقب'),
                    new OA\Property(property: 'corrective_action', type: 'string', example: 'الإجراء التصحيحي المحدث', description: 'الإجراء التصحيحي'),
                    new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-25', description: 'موعد التصحيح'),
                    new OA\Property(
                        property: 'correction_status',
                        type: 'string',
                        example: 'completed',
                        description: 'حالة التصحيح',
                        enum: ['pending', 'in_progress', 'completed', 'verified', 'waived']
                    ),
                    new OA\Property(property: 'verified_by', type: 'integer', example: 3, description: 'معرف الشخص الذي قام بالتحقق'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث الإجابة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'answer_value', type: 'string', example: 'لا'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 0.0),
                                new OA\Property(property: 'comment', type: 'string', example: 'تعليق محدث'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب المحدثة'),
                                new OA\Property(property: 'corrective_action', type: 'string', example: 'الإجراء التصحيحي المحدث'),
                                new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-25'),
                                new OA\Property(property: 'correction_status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'correction_status_text', type: 'string', example: 'مكتمل'),
                                new OA\Property(property: 'corrected_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                                new OA\Property(property: 'verified_by', type: 'integer', example: 3),
                                new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T14:30:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الإجابة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة أو لا تنتمي إلى الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة لا تنتمي إلى هذه الزيارة'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل حالة التصحيح غير صالح')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function update(UpdateAnswerRequest $request, Visit $visit, Answer $answer): JsonResponse
    {
        if ($answer->visit_id !== $visit->id) {
            return response()->json([
                'message' => 'الإجابة لا تنتمي إلى هذه الزيارة'
            ], 404);
        }
        
        $updateData = $request->only([
            'answer_value', 'score', 'comment', 'auditor_comment',
            'corrective_action', 'corrective_deadline', 'correction_status', 'verified_by'
        ]);
        
        if ($request->has('correction_status') && $request->input('correction_status') === 'completed') {
            $updateData['corrected_at'] = now();
        }
        
        if ($request->has('verified_by')) {
            $updateData['verified_at'] = now();
        }
        
        $answer->update($updateData);
        
        $this->updateVisitStatistics($visit);
        
        return response()->json([
            'data' => new AnswerResource($answer->load(['question'])),
            'message' => 'تم تحديث الإجابة بنجاح'
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/visits/{visit}/answers/{answer}',
        operationId: 'deleteAnswer',
        summary: 'حذف إجابة',
        description: 'حذف إجابة (حذف ناعم)',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف الإجابة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'visit_id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', example: '2026-02-10T15:00:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف الإجابة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة أو لا تنتمي إلى الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة لا تنتمي إلى هذه الزيارة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(Visit $visit, Answer $answer): JsonResponse
    {
        if ($answer->visit_id !== $visit->id) {
            return response()->json([
                'message' => 'الإجابة لا تنتمي إلى هذه الزيارة'
            ], 404);
        }
        
        $answer->delete();
        
        $this->updateVisitStatistics($visit);
        
        return response()->json([
            'data' => new AnswerResource($answer),
            'message' => 'تم حذف الإجابة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/answers/{answer}/restore',
        operationId: 'restoreAnswer',
        summary: 'استعادة إجابة محذوفة',
        description: 'استعادة إجابة تم حذفها (حذف ناعم)',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة الإجابة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'visit_id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                new OA\Property(property: 'answer_value', type: 'string', example: 'نعم'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', example: null),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T15:30:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة الإجابة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function restore(Visit $visit, $answerId): JsonResponse
    {
        $answer = Answer::withTrashed()
            ->where('visit_id', $visit->id)
            ->where('id', $answerId)
            ->firstOrFail();
            
        $answer->restore();
        
        $this->updateVisitStatistics($visit);
        
        return response()->json([
            'data' => new AnswerResource($answer),
            'message' => 'تم استعادة الإجابة بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/answers/{answer}/verify',
        operationId: 'verifyAnswerCorrection',
        summary: 'التحقق من التصحيح',
        description: 'التحقق من إكمال التصحيح للإجابة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'تم التحقق بنجاح', description: 'ملاحظات التحقق'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم التحقق من التصحيح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'correction_status', type: 'string', example: 'verified'),
                                new OA\Property(property: 'correction_status_text', type: 'string', example: 'تم التحقق'),
                                new OA\Property(property: 'verified_by', type: 'integer', example: 3),
                                new OA\Property(property: 'verified_at', type: 'string', format: 'date-time', example: '2026-02-10T15:45:00Z'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات التحقق: تم التحقق بنجاح'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T15:45:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم التحقق من التصحيح بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'لا يمكن التحقق من التصحيح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'لا يمكن التحقق من التصحيح إلا إذا كان مكتملاً'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة أو لا تنتمي إلى الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة لا تنتمي إلى هذه الزيارة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function verify(Visit $visit, Answer $answer, Request $request): JsonResponse
    {
        if ($answer->visit_id !== $visit->id) {
            return response()->json([
                'message' => 'الإجابة لا تنتمي إلى هذه الزيارة'
            ], 404);
        }
        
        if ($answer->correction_status !== 'completed') {
            return response()->json([
                'message' => 'لا يمكن التحقق من التصحيح إلا إذا كان مكتملاً'
            ], 400);
        }
        
        $answer->update([
            'correction_status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'auditor_comment' => $request->input('notes') ? 
                ($answer->auditor_comment . "\nملاحظات التحقق: " . $request->input('notes')) : 
                $answer->auditor_comment,
        ]);
        
        return response()->json([
            'data' => new AnswerResource($answer),
            'message' => 'تم التحقق من التصحيح بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/answers/{answer}/waive',
        operationId: 'waiveAnswerCorrection',
        summary: 'إعفاء من التصحيح',
        description: 'إعفاء الإجابة من التصحيح',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'معرف الإجابة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'تم إصلاح المشكلة مسبقاً', description: 'سبب الإعفاء'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم الإعفاء من التصحيح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'correction_status', type: 'string', example: 'waived'),
                                new OA\Property(property: 'correction_status_text', type: 'string', example: 'معفى'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'سبب الإعفاء: تم إصلاح المشكلة مسبقاً'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-10T16:00:00Z'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم الإعفاء من التصحيح بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الإجابة غير موجودة أو لا تنتمي إلى الزيارة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الإجابة لا تنتمي إلى هذه الزيارة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function waive(Visit $visit, Answer $answer, Request $request): JsonResponse
    {
        if ($answer->visit_id !== $visit->id) {
            return response()->json([
                'message' => 'الإجابة لا تنتمي إلى هذه الزيارة'
            ], 404);
        }
        
        $answer->update([
            'correction_status' => 'waived',
            'auditor_comment' => $request->input('reason') ? 
                ($answer->auditor_comment . "\nسبب الإعفاء: " . $request->input('reason')) : 
                $answer->auditor_comment,
        ]);
        
        return response()->json([
            'data' => new AnswerResource($answer),
            'message' => 'تم الإعفاء من التصحيح بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/visits/{visit}/answers/bulk',
        operationId: 'bulkCreateAnswers',
        summary: 'تسجيل إجابات جماعية',
        description: 'تسجيل مجموعة من الإجابات دفعة واحدة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['answers'],
                properties: [
                    new OA\Property(
                        property: 'answers',
                        type: 'array',
                        description: 'قائمة الإجابات',
                        items: new OA\Items(
                            type: 'object',
                            required: ['question_id', 'answer_value'],
                            properties: [
                                new OA\Property(property: 'question_id', type: 'integer', example: 1, description: 'معرف السؤال'),
                                new OA\Property(property: 'answer_value', type: 'string', example: 'نعم', description: 'قيمة الإجابة'),
                                new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0, description: 'الدرجة'),
                                new OA\Property(property: 'comment', type: 'string', example: 'تعليق', description: 'تعليق'),
                                new OA\Property(property: 'auditor_comment', type: 'string', example: 'ملاحظات المراقب', description: 'تعليق المراقب'),
                                new OA\Property(property: 'corrective_action', type: 'string', example: 'تحسين النظافة', description: 'الإجراء التصحيحي'),
                                new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20', description: 'موعد التصحيح'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم تسجيل الإجابات',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'answer_value', type: 'string', example: 'نعم'),
                                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                                    new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                    new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                    new OA\Property(property: 'comment', type: 'string', example: 'تعليق'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                ]
                            )
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم تسجيل الإجابات بنجاح'),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_created', type: 'integer', example: 5),
                                new OA\Property(property: 'total_score', type: 'number', format: 'float', example: 45.0),
                                new OA\Property(property: 'average_score', type: 'number', format: 'float', example: 9.0),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'بيانات غير صالحة'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string', example: 'حقل قائمة الإجابات مطلوب')
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function bulkStore(Visit $visit, BulkAnswerRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($visit, $request) {
            $answers = [];
            $questions = Question::whereIn('id', collect($request->input('answers'))->pluck('question_id'))->get()->keyBy('id');
            
            foreach ($request->input('answers') as $answerData) {
                $question = $questions[$answerData['question_id']] ?? null;
                if (!$question) {
                    continue;
                }
                
                $answer = Answer::create([
                    'visit_id' => $visit->id,
                    'question_id' => $answerData['question_id'],
                    'answer_value' => $answerData['answer_value'],
                    'score' => $answerData['score'] ?? 0,
                    'max_score' => $question->max_score,
                    'is_correct' => $this->checkIfCorrect($question, $answerData['answer_value']),
                    'comment' => $answerData['comment'] ?? null,
                    'auditor_comment' => $answerData['auditor_comment'] ?? null,
                    'corrective_action' => $answerData['corrective_action'] ?? null,
                    'corrective_deadline' => $answerData['corrective_deadline'] ?? null,
                    'correction_status' => isset($answerData['corrective_action']) ? 'pending' : null,
                ]);
                
                $answers[] = $answer;
            }
            
            $this->updateVisitStatistics($visit);
            
            $totalScore = collect($answers)->sum('score');
            $averageScore = count($answers) > 0 ? $totalScore / count($answers) : 0;
            
            return response()->json([
                'data' => AnswerResource::collection(collect($answers)->load(['question'])),
                'message' => 'تم تسجيل الإجابات بنجاح',
                'statistics' => [
                    'total_created' => count($answers),
                    'total_score' => $totalScore,
                    'average_score' => round($averageScore, 2),
                ],
            ], 201);
        });
    }
    
    #[OA\Get(
        path: '/api/v1/visits/{visit}/answers/corrections',
        operationId: 'getCorrections',
        summary: 'عرض التصحيحات المطلوبة',
        description: 'استرجاع جميع التصحيحات المطلوبة لزيارة معينة',
        tags: ['Answers'],
        //security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'visit',
                description: 'معرف الزيارة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'status',
                description: 'فلتر بحالة التصحيح',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['pending', 'in_progress', 'completed', 'verified']
                )
            ),
            new OA\Parameter(
                name: 'overdue_only',
                description: 'عرض التصحيحات المتأخرة فقط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة التصحيحات',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'question_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'answer_value', type: 'string', example: 'لا'),
                                    new OA\Property(property: 'score', type: 'number', format: 'float', example: 0.0),
                                    new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                    new OA\Property(property: 'corrective_action', type: 'string', example: 'تحسين النظافة'),
                                    new OA\Property(property: 'corrective_deadline', type: 'string', format: 'date', example: '2026-02-20'),
                                    new OA\Property(property: 'correction_status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'correction_status_text', type: 'string', example: 'قيد الانتظار'),
                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-10T09:15:00Z'),
                                    new OA\Property(
                                        property: 'question',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                            new OA\Property(property: 'type_text', type: 'string', example: 'نعم/لا'),
                                            new OA\Property(property: 'is_critical', type: 'boolean', example: true),
                                        ]
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'statistics',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 8),
                                new OA\Property(property: 'pending', type: 'integer', example: 3),
                                new OA\Property(property: 'in_progress', type: 'integer', example: 2),
                                new OA\Property(property: 'completed', type: 'integer', example: 2),
                                new OA\Property(property: 'verified', type: 'integer', example: 1),
                                new OA\Property(property: 'waived', type: 'integer', example: 0),
                                new OA\Property(property: 'overdue', type: 'integer', example: 1),
                                new OA\Property(property: 'critical_count', type: 'integer', example: 3),
                                new OA\Property(property: 'average_days_remaining', type: 'number', format: 'float', example: 5.3),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'الزيارة غير موجودة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'الزيارة غير موجودة'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'غير مصرح به',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'غير مصرح به'),
                    ]
                )
            ),
        ]
    )]
    public function corrections(Visit $visit, Request $request): JsonResponse
    {
        $query = $visit->answers()->whereNotNull('corrective_action');
        
        if ($request->has('status')) {
            $query->where('correction_status', $request->input('status'));
        }
        
        if ($request->boolean('overdue_only')) {
            $query->where('corrective_deadline', '<', now()->toDateString())
                  ->whereNotIn('correction_status', ['completed', 'verified', 'waived']);
        }
        
        $answers = $query->with(['question'])->get();
        
        // حساب الإحصائيات
        $total = $answers->count();
        $pending = $answers->where('correction_status', 'pending')->count();
        $inProgress = $answers->where('correction_status', 'in_progress')->count();
        $completed = $answers->where('correction_status', 'completed')->count();
        $verified = $answers->where('correction_status', 'verified')->count();
        $waived = $answers->where('correction_status', 'waived')->count();
        $overdue = $answers->filter(function ($answer) {
            return $answer->isOverdue();
        })->count();
        
        $criticalCount = $answers->filter(function ($answer) {
            return $answer->question && $answer->question->is_critical;
        })->count();
        
        // حساب متوسط الأيام المتبقية
        $daysRemaining = [];
        foreach ($answers as $answer) {
            if ($answer->corrective_deadline && !in_array($answer->correction_status, ['completed', 'verified', 'waived'])) {
                $days = now()->diffInDays($answer->corrective_deadline, false);
                if ($days > 0) {
                    $daysRemaining[] = $days;
                }
            }
        }
        $averageDaysRemaining = count($daysRemaining) > 0 ? array_sum($daysRemaining) / count($daysRemaining) : 0;
        
        return response()->json([
            'data' => AnswerResource::collection($answers),
            'statistics' => [
                'total' => $total,
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'verified' => $verified,
                'waived' => $waived,
                'overdue' => $overdue,
                'critical_count' => $criticalCount,
                'average_days_remaining' => round($averageDaysRemaining, 1),
            ],
        ]);
    }
    
    private function checkIfCorrect(Question $question, $answerValue): ?bool
    {
        if (!$question->correct_answer) {
            return null;
        }
        
        return $answerValue === $question->correct_answer;
    }
    
    private function updateVisitStatistics(Visit $visit): void
    {
        $answers = $visit->answers;
        
        if ($answers->isEmpty()) {
            return;
        }
        
        $totalScore = $answers->sum('score');
        $maxPossibleScore = $answers->sum('max_score');
        $percentage = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
        
        $category = $this->determineCategory($percentage);
        
        $visit->update([
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'percentage' => $percentage,
            'category' => $category,
        ]);
    }
    
    private function determineCategory(float $percentage): string
    {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }
    */
}