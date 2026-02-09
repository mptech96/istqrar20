<?php

namespace App\Services\V1;

use OpenAI\Laravel\Facades\OpenAI;

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
}