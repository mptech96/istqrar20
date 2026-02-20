<?php

namespace App\Services\V1;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    public function generateBranchSuggestion(array $data): array
    {
        $prompt = $this->buildPrompt($data);

        $start = microtime(true);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        'أنت خبير تدقيق جودة. مهمتك اقتراح حلول عملية وقابلة للتطبيق لرفع نقاط التقييم.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.4,
            'max_tokens' => 500,
        ]);

        $processingTime = (int) ((microtime(true) - $start) * 1000);

        return [
            'text' => $response->choices[0]->message->content,
            'tokens' => $response->usage->totalTokens ?? null,
            'model' => $response->model,
            'processing_time' => $processingTime,
        ];
    }

    private function buildPrompt(array $data): string
    {
        return "
الفرع حصل على تقييم {$data['score']}%.

عدد الأخطاء الحرجة: {$data['critical_errors']}
النقاط المفقودة: {$data['lost_points']}

نقاط الضعف:
{$data['weaknesses']}

المطلوب:
- اقتراح حلول عملية لرفع التقييم
- ذكر كيف يساهم كل حل في زيادة النقاط
- بدون كلام عام أو تنظير
";
    }
    
    public function deepAnalysis(string $prompt, string $analysisType): array
    {
        $start = microtime(true);

        // تعريف الرسالة التوجيهية (System Prompt) حسب نوع التحليل
        $systemPrompts = [
            'root_cause'    => 'أنت خبير تحليل أسباب جذرية متخصص في تحديد المشاكل الأساسية في أنظمة الجودة.',
            'patterns'      => 'أنت خبير تحليل أنماط متخصص في اكتشاف التكرارات والعلاقات في بيانات التقييم.',
            'predictive'    => 'أنت خبير تحليل تنبؤي متخصص في توقع النتائج المستقبلية بناءً على البيانات التاريخية.',
            'comparative'   => 'أنت خبير تحليل مقارن متخصص في مقارنة الأداء بين الفروع المختلفة.',
            'recommendations' => 'أنت خبير تطوير وتدريب متخصص في اقتراح حلول عملية قابلة للتنفيذ.',
            'risk'          => 'أنت خبير إدارة مخاطر متخصص في تحديد وتقييم المخاطر في بيئات العمل.',
            'action_plan'   => 'أنت مستشار إداري متخصص في بناء خطط عمل متكاملة ومنظمة.',
        ];

        $systemPrompt = $systemPrompts[$analysisType] ?? 'أنت خبير تحليل بيانات متقدم.';

        try {
            $response = OpenAI::chat()->create([
                'model' => setting('deep_analysis_model', 'gpt-4', 'ai'),
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt . ' يجب أن تكون الإجابات دقيقة ومحددة وعملية. أجب بصيغة JSON فقط.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ],
                ],
                'temperature' => (float) setting('deep_analysis_temperature', 0.2, 'ai'),
                'max_tokens'  => (int) setting('deep_analysis_max_tokens', 2000, 'ai'),
                'response_format' => ['type' => 'json_object'],
            ]);

            $processingTime = (int) ((microtime(true) - $start) * 1000);

            return [
                'text'            => $response->choices[0]->message->content,
                'tokens'          => $response->usage->totalTokens ?? 0,
                'model'           => $response->model,
                'processing_time' => $processingTime,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI deepAnalysis failed', [
                'type'    => $analysisType,
                'error'   => $e->getMessage(),
            ]);

            // إعادة استجابة افتراضية في حالة الفشل
            return [
                'text'            => json_encode(['error' => 'فشل الاتصال بخدمة الذكاء الاصطناعي']),
                'tokens'          => 0,
                'model'           => 'none',
                'processing_time' => (int) ((microtime(true) - $start) * 1000),
            ];
        }
    }
    
    public function quickAnalysis(string $question, string $answer): array
    {
        $prompt = "
السؤال: {$question}
الإجابة: {$answer}

قم بتحليل سريع وتقديم توصية مختصرة (جملة أو جملتين).
";

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'أنت مساعد ذكي مختص بالجودة. قدم تحليلاً سريعاً ومختصراً.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 150,
            ]);

            return [
                'text'   => $response->choices[0]->message->content,
                'tokens' => $response->usage->totalTokens ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI quickAnalysis failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'text'   => 'عذراً، تعذر إجراء التحليل السريع حالياً.',
                'tokens' => 0,
            ];
        }
    }
    
    public function estimateCost(int $tokens, string $model = 'gpt-4'): float
    {
        // أسعار تقريبية لكل 1000 توكن (راجع أحدث الأسعار من OpenAI)
        $rates = [
            'gpt-4'          => 0.03,  // $0.03 / 1K tokens
            'gpt-4-turbo'    => 0.01,
            'gpt-3.5-turbo'  => 0.002,
        ];

        $rate = $rates[$model] ?? $rates['gpt-4'];
        return ($tokens / 1000) * $rate;
    }
}