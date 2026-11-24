<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreQuestionRequest;
use App\Http\Requests\V1\UpdateQuestionRequest;
use App\Http\Resources\V1\QuestionResource;
use App\Models\V1\Checklist;
use App\Models\V1\Question;
use App\Models\V1\Visit;
use App\Models\V1\Criteria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class QuestionController extends Controller
{
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}/questions',
        operationId: 'getChecklistQuestions',
        summary: 'عرض أسئلة قائمة مراجعة',
        description: 'استرجاع قائمة أسئلة قائمة مراجعة معينة',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'is_active',
                description: 'فلتر بالنشاط',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'is_critical',
                description: 'فلتر بالأسئلة الحرجة',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'type',
                description: 'فلتر بنوع السؤال',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number']
                )
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'ترتيب النتائج حسب',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'sort_order',
                    enum: ['sort_order', 'weight', 'max_score', 'created_at']
                )
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'اتجاه الترتيب',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'criteria,checklist')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'قائمة أسئلة القائمة',
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
                                    new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                    new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                    new OA\Property(property: 'weight', type: 'integer', example: 5),
                                    new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                    new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                    new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'القائمة غير موجودة'
            ),
        ]
    )]
    public function index(Checklist $checklist, Request $request): JsonResponse
    {
        $query = $checklist->questions();
        
        // Filtering
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('is_critical')) {
            $query->where('is_critical', $request->boolean('is_critical'));
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['criteria', 'checklist'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $questions = $query->with($with)->get();
        
        return response()->json([
            'data' => QuestionResource::collection($questions)
        ]);
    }
    
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}/questions/types',
        operationId: 'getQuestionTypes',
        summary: 'عرض أنواع الأسئلة المتاحة',
        description: 'استرجاع قائمة أنواع الأسئلة المتاحة مع وصفها',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'أنواع الأسئلة',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'value', type: 'string', example: 'yes_no'),
                                    new OA\Property(property: 'label', type: 'string', example: 'نعم/لا'),
                                    new OA\Property(property: 'description', type: 'string', example: 'سؤال بإجابة نعم أو لا'),
                                    new OA\Property(property: 'has_options', type: 'boolean', example: false),
                                    new OA\Property(property: 'has_correct_answer', type: 'boolean', example: false),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function questionTypes(Checklist $checklist): JsonResponse
    {
        $types = [
            [
                'value' => 'yes_no',
                'label' => 'نعم/لا',
                'description' => 'سؤال بإجابة نعم أو لا',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'scale_1_5',
                'label' => 'مقياس من 1 إلى 5',
                'description' => 'سؤال بمقياس من 1 (ضعيف) إلى 5 (ممتاز)',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'scale_1_10',
                'label' => 'مقياس من 1 إلى 10',
                'description' => 'سؤال بمقياس من 1 (ضعيف) إلى 10 (ممتاز)',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'multiple_choice',
                'label' => 'اختيار متعدد',
                'description' => 'سؤال باختيارات متعددة مع درجات مختلفة',
                'has_options' => true,
                'has_correct_answer' => true,
            ],
            [
                'value' => 'text',
                'label' => 'نص',
                'description' => 'سؤال بإجابة نصية',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'file',
                'label' => 'ملف',
                'description' => 'سؤال يتطلب رفع ملف',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'date',
                'label' => 'تاريخ',
                'description' => 'سؤال بإجابة تاريخ',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
            [
                'value' => 'number',
                'label' => 'رقم',
                'description' => 'سؤال بإجابة رقمية',
                'has_options' => false,
                'has_correct_answer' => false,
            ],
        ];
        
        return response()->json([
            'data' => $types
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/checklists/{checklist}/questions',
        operationId: 'createQuestion',
        summary: 'إضافة سؤال جديد',
        description: 'إضافة سؤال جديد إلى قائمة مراجعة
        <br>
        استخدم نقطة 
        <br>
        /api/v1/questions/create
        <br>
        لعرض بيانات القوائم والتقييمات ونوع السؤال
        ',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['question_text', 'type', 'weight', 'max_score'],
                properties: [
                    new OA\Property(property: 'criteria_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                    new OA\Property(property: 'description', type: 'string', example: 'تقييم النظافة العامة للمكان'),
                    new OA\Property(property: 'help_text', type: 'string', example: 'ركز على النظافة العامة وليس التفاصيل'),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number'],
                        example: 'yes_no'
                    ),
                    new OA\Property(
                        property: 'options',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'value', type: 'string', example: 'نعم'),
                                new OA\Property(property: 'score', type: 'integer', example: 10),
                                new OA\Property(property: 'description', type: 'string', example: 'نعم، المكان نظيف'),
                            ]
                        )
                    ),
                    new OA\Property(property: 'correct_answer', type: 'string', example: 'نعم', nullable: true),
                    new OA\Property(property: 'weight', type: 'integer', example: 5),
                    new OA\Property(property: 'max_score', type: 'integer', example: 10),
                    new OA\Property(property: 'passing_score', type: 'integer', example: 6, nullable: true),
                    new OA\Property(property: 'is_required', type: 'boolean', example: true),
                    new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                    new OA\Property(property: 'has_attachment', type: 'boolean', example: false),
                    new OA\Property(property: 'attachment_instructions', type: 'string', example: 'ارفع صورة للمكان', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم إضافة السؤال بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                new OA\Property(property: 'weight', type: 'integer', example: 5),
                                new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم إضافة السؤال بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function store(Checklist $checklist, StoreQuestionRequest $request): JsonResponse
    {
        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن تعديل أسئلة قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات نتائج الزيارات. أنشئ نسخة جديدة من القائمة للتعديلات.',
            ], 409);
        }

        $questionData = [
            'checklist_id' => $checklist->id,
            'criteria_id' => $request->input('criteria_id'),
            'question_text' => $request->input('question_text'),
            'description' => $request->input('description'),
            'help_text' => $request->input('help_text'),
            'type' => $request->input('type'),
            'weight' => $request->input('weight'),
            'max_score' => $request->input('max_score'),
            'passing_score' => $request->input('passing_score'),
            'is_required' => $request->input('is_required', true),
            'is_critical' => $request->input('is_critical', false),
            'has_attachment' => $request->input('has_attachment', false),
            'attachment_instructions' => $request->input('attachment_instructions'),
            'sort_order' => $request->input('sort_order', 0),
            'is_active' => $request->input('is_active', true),
        ];
        
        // Handle options for multiple choice questions
        if ($request->input('type') === 'multiple_choice' && $request->has('options')) {
            $questionData['options'] = $request->input('options');
        }
        if ($request->exists('correct_answer')) {
            $questionData['correct_answer'] = $request->input('correct_answer');
        }
        
        $question = Question::create($questionData);
        
        return response()->json([
            'data' => new QuestionResource($question->load(['criteria', 'checklist'])),
            'message' => 'تم إضافة السؤال بنجاح'
        ], 201);
    }
    
    #[OA\Get(
        path: '/api/v1/questions/create',
        operationId: 'getriteriasChecklists',
        summary: 'بيانات انشاء سؤال',
        description: 'عرض القوائم والتقييمات',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'criterias',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'الجودة'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'checklists',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'قائمة 1'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'types',
                            type: 'array',
                            example: ['yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number'],
                            items: new OA\Items(
                                type: 'string'
                            )
                        )

                    ]
                )
            ),
        ]
    )]
    public function create()
    {
        return response()->json([
            'criterias' => Criteria::select(['id', 'name'])->get(),
            'checklists' => Checklist::select(['id', 'name'])->get(),
            'type' => ['yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number'],
        ]);
    }
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}/questions/{question}',
        operationId: 'getQuestion',
        summary: 'عرض تفاصيل سؤال',
        description: 'استرجاع تفاصيل سؤال معين',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'include',
                description: 'العلاقات المطلوب تضمينها',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'criteria,checklist')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تفاصيل السؤال',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                new OA\Property(property: 'description', type: 'string', example: 'تقييم النظافة العامة للمكان'),
                                new OA\Property(property: 'help_text', type: 'string', example: 'ركز على النظافة العامة وليس التفاصيل'),
                                new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                new OA\Property(property: 'weight', type: 'integer', example: 5),
                                new OA\Property(property: 'max_score', type: 'integer', example: 10),
                                new OA\Property(property: 'passing_score', type: 'integer', example: 6),
                                new OA\Property(property: 'is_required', type: 'boolean', example: true),
                                new OA\Property(property: 'is_critical', type: 'boolean', example: false),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
        ]
    )]
    public function show(Checklist $checklist, Question $question, Request $request): JsonResponse
    {
        // Ensure the question belongs to the checklist
        if ($question->checklist_id !== $checklist->id) {
            return response()->json([
                'message' => 'السؤال لا ينتمي إلى هذه القائمة'
            ], 404);
        }
        
        // Include relationships
        $with = [];
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            foreach ($includes as $include) {
                if (in_array(trim($include), ['criteria', 'checklist'])) {
                    $with[] = trim($include);
                }
            }
        }
        
        $question->load($with);
        
        return response()->json([
            'data' => new QuestionResource($question)
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/checklists/{checklist}/questions/{question}',
        operationId: 'updateQuestion',
        summary: 'تحديث بيانات سؤال',
        description: 'تحديث بيانات سؤال موجود',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال',
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
                    new OA\Property(property: 'criteria_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟ (محدث)'),
                    new OA\Property(property: 'description', type: 'string', example: 'تقييم النظافة العامة للمكان - محدث'),
                    new OA\Property(property: 'help_text', type: 'string', example: 'ركز على النظافة العامة وليس التفاصيل - محدث'),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number'],
                        example: 'yes_no'
                    ),
                    new OA\Property(
                        property: 'options',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'value', type: 'string', example: 'نعم'),
                                new OA\Property(property: 'score', type: 'integer', example: 10),
                                new OA\Property(property: 'description', type: 'string', example: 'نعم، المكان نظيف'),
                            ]
                        )
                    ),
                    new OA\Property(property: 'correct_answer', type: 'string', example: 'نعم', nullable: true),
                    new OA\Property(property: 'weight', type: 'integer', example: 6),
                    new OA\Property(property: 'max_score', type: 'integer', example: 12),
                    new OA\Property(property: 'passing_score', type: 'integer', example: 8, nullable: true),
                    new OA\Property(property: 'is_required', type: 'boolean', example: true),
                    new OA\Property(property: 'is_critical', type: 'boolean', example: true),
                    new OA\Property(property: 'has_attachment', type: 'boolean', example: true),
                    new OA\Property(property: 'attachment_instructions', type: 'string', example: 'ارفع صورة للمكان من زوايا مختلفة', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', example: 2),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم تحديث السؤال بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'تم تحديث السؤال بنجاح'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟ (محدث)'),
                                new OA\Property(property: 'type', type: 'string', example: 'yes_no'),
                                new OA\Property(property: 'weight', type: 'integer', example: 6),
                                new OA\Property(property: 'max_score', type: 'integer', example: 12),
                                new OA\Property(property: 'is_critical', type: 'boolean', example: true),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function update(
        UpdateQuestionRequest $request,
        Checklist $checklist,
        Question $question
    ): JsonResponse {
        // Ensure the question belongs to the checklist
        if ($question->checklist_id !== $checklist->id) {
            return response()->json([
                'message' => 'السؤال لا ينتمي إلى هذه القائمة'
            ], 404);
        }

        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن تعديل أسئلة قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات نتائج الزيارات. أنشئ نسخة جديدة من القائمة للتعديلات.',
            ], 409);
        }
        
        $questionData = $request->only([
            'criteria_id', 'question_text', 'description', 'help_text',
            'type', 'weight', 'max_score', 'passing_score', 'is_required',
            'is_critical', 'has_attachment', 'attachment_instructions',
            'sort_order', 'is_active'
        ]);
        
        // Handle options even when the client omits the unchanged question type.
        $effectiveType = $request->input('type', $question->type);
        if ($effectiveType === 'multiple_choice') {
            if ($request->has('options')) {
                $questionData['options'] = $request->input('options');
            }
            if ($request->exists('correct_answer')) {
                $questionData['correct_answer'] = $request->input('correct_answer');
            }
        } else {
            if ($request->has('type') && $question->type === 'multiple_choice') {
                $questionData['options'] = null;
                $questionData['correct_answer'] = null;
            }
            if ($request->exists('correct_answer')) {
                $questionData['correct_answer'] = $request->input('correct_answer');
            }
        }
        
        $question->update($questionData);
        
        return response()->json([
            'data' => new QuestionResource($question->load(['criteria', 'checklist'])),
            'message' => 'تم تحديث السؤال بنجاح'
        ]);
    }
    
    #[OA\Delete(
        path: '/api/v1/checklists/{checklist}/questions/{question}',
        operationId: 'deleteQuestion',
        summary: 'حذف سؤال',
        description: 'حذف سؤال معين (حذف ناعم)',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم حذف السؤال بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم حذف السؤال بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'is_active', type: 'boolean', example: false),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-02-01T12:00:00Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
        ]
    )]
    public function destroy(Checklist $checklist, Question $question): JsonResponse
    {
        // Ensure the question belongs to the checklist
        if ($question->checklist_id !== $checklist->id) {
            return response()->json([
                'message' => 'السؤال لا ينتمي إلى هذه القائمة'
            ], 404);
        }

        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن حذف سؤال من قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات نتائج الزيارات.',
            ], 409);
        }
        
        $question->delete();
        
        return response()->json([
            'data' => new QuestionResource($question),
            'message' => 'تم حذف السؤال بنجاح'
        ]);
    }
    
    #[OA\Post(
        path: '/api/v1/checklists/{checklist}/questions/{question}/restore',
        operationId: 'restoreQuestion',
        summary: 'استعادة سؤال محذوف',
        description: 'استعادة سؤال تم حذفه (حذف ناعم)',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم استعادة السؤال بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم استعادة السؤال بنجاح'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'question_text', type: 'string', example: 'هل المكان نظيف بشكل عام؟'),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
        ]
    )]
    public function restore(Checklist $checklist, $questionId): JsonResponse
    {
        $question = Question::withTrashed()
            ->where('checklist_id', $checklist->id)
            ->where('id', $questionId)
            ->firstOrFail();

        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن استعادة أسئلة قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات نتائج الزيارات.',
            ], 409);
        }
            
        $question->restore();
        
        return response()->json([
            'data' => new QuestionResource($question),
            'message' => 'تم استعادة السؤال بنجاح'
        ]);
    }
    
    #[OA\Put(
        path: '/api/v1/checklists/{checklist}/questions/reorder',
        operationId: 'reorderQuestions',
        summary: 'إعادة ترتيب الأسئلة',
        description: 'إعادة ترتيب أسئلة قائمة مراجعة',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['questions_order'],
                properties: [
                    new OA\Property(
                        property: 'questions_order',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['id', 'sort_order'],
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'تم إعادة الترتيب بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'تم إعادة ترتيب الأسئلة بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'بيانات غير صالحة'
            ),
        ]
    )]
    public function reorder(Checklist $checklist, Request $request): JsonResponse
    {
        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن إعادة ترتيب أسئلة قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات سجل التقييم.',
            ], 409);
        }

        $request->validate([
            'questions_order' => ['required', 'array', 'min:1'],
            'questions_order.*.id' => ['required', 'exists:questions,id'],
            'questions_order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);
        
        return DB::transaction(function () use ($checklist, $request) {
            $questionsOrder = $request->input('questions_order');
            
            // Verify all questions belong to the checklist
            $questionIds = collect($questionsOrder)->pluck('id')->toArray();
            $invalidQuestions = $checklist->questions()
                ->whereIn('id', $questionIds)
                ->count() !== count($questionIds);
                
            if ($invalidQuestions) {
                return response()->json([
                    'message' => 'بعض الأسئلة لا تنتمي إلى هذه القائمة'
                ], 422);
            }
            
            // Update questions order
            foreach ($questionsOrder as $order) {
                $checklist->questions()
                    ->where('id', $order['id'])
                    ->update(['sort_order' => $order['sort_order']]);
            }
            
            // Reload questions ordered by sort_order
            $questions = $checklist->questions()->orderBy('sort_order')->get();
            
            return response()->json([
                'data' => QuestionResource::collection($questions),
                'message' => 'تم إعادة ترتيب الأسئلة بنجاح'
            ]);
        });
    }
    
    /*
    #[OA\Post(
        path: '/api/v1/checklists/{checklist}/questions/{question}/duplicate',
        operationId: 'duplicateQuestion',
        summary: 'نسخ سؤال',
        description: 'إنشاء نسخة من سؤال موجود في نفس القائمة',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال الأصلي',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'تم نسخ السؤال بنجاح',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 2),
                                new OA\Property(property: 'question_text', type: 'string', example: 'نسخة: هل المكان نظيف بشكل عام؟'),
                                new OA\Property(property: 'checklist_id', type: 'integer', example: 1),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'تم نسخ السؤال بنجاح'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
        ]
    )]
    */
    public function duplicate(Checklist $checklist, Question $question): JsonResponse
    {
        if ($this->checklistHasVisits($checklist)) {
            return response()->json([
                'message' => 'لا يمكن نسخ سؤال إلى قائمة مرتبطة بزيارات موجودة حفاظاً على ثبات نتائج الزيارات. أنشئ نسخة جديدة من القائمة للتعديلات.',
            ], 409);
        }

        // Ensure the question belongs to the checklist
        if ($question->checklist_id !== $checklist->id) {
            return response()->json([
                'message' => 'السؤال لا ينتمي إلى هذه القائمة'
            ], 404);
        }
        
        // Get the next sort order
        $nextSortOrder = $checklist->questions()->max('sort_order') + 1;
        
        // Create duplicate
        $newQuestion = $question->replicate();
        $newQuestion->question_text = 'نسخة: ' . $question->question_text;
        $newQuestion->sort_order = $nextSortOrder;
        $newQuestion->save();
        
        return response()->json([
            'data' => new QuestionResource($newQuestion),
            'message' => 'تم نسخ السؤال بنجاح'
        ], 201);
    }
    
    /*
    #[OA\Get(
        path: '/api/v1/checklists/{checklist}/questions/{question}/evaluate',
        operationId: 'evaluateQuestionAnswer',
        summary: 'تقييم إجابة سؤال',
        description: 'تقييم إجابة سؤال وحساب النقاط بناءً على نوع السؤال',
        tags: ['Questions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'checklist',
                description: 'معرف القائمة',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'question',
                description: 'معرف السؤال',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'answer',
                description: 'الإجابة المراد تقييمها',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    oneOf: [
                        new OA\Schema(type: 'string', example: 'نعم'),
                        new OA\Schema(type: 'integer', example: 8),
                        new OA\Schema(type: 'number', format: 'float', example: 7.5),
                    ]
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'نتيجة التقييم',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'question_id', type: 'integer', example: 1),
                        new OA\Property(property: 'answer', type: 'string', example: 'نعم'),
                        new OA\Property(property: 'score', type: 'number', format: 'float', example: 10.0),
                        new OA\Property(property: 'max_score', type: 'integer', example: 10),
                        new OA\Property(property: 'is_passing', type: 'boolean', example: true),
                        new OA\Property(property: 'is_correct', type: 'boolean', example: true, nullable: true),
                        new OA\Property(property: 'weighted_score', type: 'number', format: 'float', example: 5.0),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'الإجابة غير صالحة لهذا النوع من الأسئلة'
            ),
            new OA\Response(
                response: 404,
                description: 'السؤال غير موجود'
            ),
        ]
    )]
    */
    public function evaluate(Checklist $checklist, Question $question, Request $request): JsonResponse
    {
        if ((int) $question->checklist_id !== (int) $checklist->id) {
            return response()->json([
                'message' => 'السؤال المحدد لا ينتمي إلى قائمة التقييم المطلوبة.',
            ], 404);
        }

        $request->validate([
            'answer' => ['required'],
        ]);
        
        $answer = $request->input('answer');
        $result = $this->calculateScore($checklist, $question, $answer);
        
        if (!$result) {
            return response()->json([
                'message' => 'الإجابة غير صالحة لهذا النوع من الأسئلة'
            ], 400);
        }
        
        // Calculate weighted score
        $weightedScore = ($result['score'] / $question->max_score) * $question->weight;
        
        return response()->json([
            'question_id' => $question->id,
            'question_text' => $question->question_text,
            'answer' => $answer,
            'score' => $result['score'],
            'max_score' => $question->max_score,
            'is_passing' => $question->passing_score ? $result['score'] >= $question->passing_score : null,
            'is_correct' => $result['is_correct'] ?? null,
            'weighted_score' => round($weightedScore, 2),
        ]);
    }
    
    private function calculateScore(Checklist $checklist, Question $question, $answer): ?array
    {
        switch ($question->type) {
            case 'yes_no':
                return $this->evaluateYesNo($checklist, $question, $answer);
                
            case 'scale_1_5':
            case 'scale_1_10':
                return $this->evaluateScale($checklist, $question, $answer);
                
            case 'multiple_choice':
                return $this->evaluateMultipleChoice($checklist, $question, $answer);
                
            case 'text':
            case 'file':
            case 'date':
                // For these types, we might need manual evaluation
                return [
                    'score' => 0,
                    'is_correct' => null,
                ];
                
            case 'number':
                return $this->evaluateNumber($checklist, $question, $answer);
                
            default:
                return null;
        }
    }
    
    private function evaluateYesNo(Checklist $checklist, Question $question, $answer): array
    {
        $normalizedAnswer = strtolower(trim($answer));
        $isYes = in_array($normalizedAnswer, ['نعم', 'yes', 'true', '1', 'صحيح']);
        $isNo = in_array($normalizedAnswer, ['لا', 'no', 'false', '0', 'خطأ']);
        
        if (!$isYes && !$isNo) {
            return ['score' => 0, 'is_correct' => false];
        }
        
        // For yes/no questions, we can assign full score for "yes" if no specific scoring
        $score = $isYes ? $question->max_score : 0;
        $isCorrect = $question->correct_answer ? 
            ($normalizedAnswer === strtolower(trim($question->correct_answer))) : null;
            
        return ['score' => $score, 'is_correct' => $isCorrect];
    }
    
    private function evaluateScale(Checklist $checklist, Question $question, $answer): array
    {
        $maxScale = $question->type === 'scale_1_5' ? 5 : 10;
        
        if (!is_numeric($answer)) {
            return ['score' => 0, 'is_correct' => null];
        }
        
        $answer = floatval($answer);
        
        if ($answer < 1 || $answer > $maxScale) {
            return ['score' => 0, 'is_correct' => null];
        }
        
        // Calculate score based on scale (linear mapping)
        $score = ($answer / $maxScale) * $question->max_score;
        
        return ['score' => $score, 'is_correct' => null];
    }
    
    private function evaluateMultipleChoice(Checklist $checklist, Question $question, $answer): array
    {
        if (!$question->options) {
            return ['score' => 0, 'is_correct' => false];
        }
        
        $options = $question->getOptionsArray();
        $normalizedAnswer = trim((string) $answer);
        
        foreach ($options as $option) {
            if (trim((string) ($option['value'] ?? '')) === $normalizedAnswer) {
                $isCorrect = $question->correct_answer !== null && $question->correct_answer !== ''
                    ? ($normalizedAnswer === trim((string) $question->correct_answer)) : null;
                    
                return [
                    'score' => min((float) $question->max_score, max(0, (float) ($option['score'] ?? 0))),
                    'is_correct' => $isCorrect,
                ];
            }
        }
        
        return ['score' => 0, 'is_correct' => false];
    }
    
    private function evaluateNumber(Checklist $checklist, Question $question, $answer): array
    {
        if (!is_numeric($answer)) {
            return ['score' => 0, 'is_correct' => null];
        }
        
        // For number questions, we might need custom scoring logic
        // For now, return full score if answer is provided
        return ['score' => $question->max_score, 'is_correct' => null];
    }

    /**
     * A checklist's question definition must remain immutable after visits exist.
     * Historical score snapshots live in visits.max_possible_score and answers.max_score;
     * blocking mutations prevents the remaining question metadata from drifting.
     */
    private function checklistHasVisits(Checklist $checklist): bool
    {
        return Visit::withoutGlobalScopes()
            ->where('checklist_id', $checklist->id)
            ->exists();
    }

}
