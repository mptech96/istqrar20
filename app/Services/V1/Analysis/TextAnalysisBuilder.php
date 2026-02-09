<?php

namespace App\Services\V1\Analysis;

class TextAnalysisBuilder
{
    public function summary(): string
    {
        return "تم تنفيذ الزيارة بنسبة {$this->percentage()}% مع وجود أخطاء حرجة.";
    }

    public function strengths(): string
    {
        return "التزام عالي في المعايير التشغيلية.";
    }

    public function weaknesses(): string
    {
        return "ضعف في معايير السلامة والتوثيق.";
    }

    private function percentage(): float
    {
        return round(
            ($this->calculator->totalScore() / max(1, $this->calculator->potentialScore())) * 100,
            2
        );
    }
}