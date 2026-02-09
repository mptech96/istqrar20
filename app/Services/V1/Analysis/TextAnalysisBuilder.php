<?php

namespace App\Services\V1\Analysis;

namespace App\Services\V1\Analysis;

use App\Models\V1\Visit;

class TextAnalysisBuilder
{
    protected Visit $visit;
    protected ScoreCalculator $calculator;
    protected ErrorClassifier $errors;

    public function __construct(
        Visit $visit,
        ScoreCalculator $calculator,
        ErrorClassifier $errors
    ) {
        $this->visit      = $visit;
        $this->calculator = $calculator;
        $this->errors     = $errors;
    }

    public function summary(): string
    {
        return sprintf(
            'تم تنفيذ الزيارة بنسبة %.2f%% مع وجود %d أخطاء حرجة.',
            $this->percentage(),
            $this->errors->critical()
        );
    }

    public function strengths(): string
    {
        return 'التزام جيد بالمعايير التشغيلية.';
    }

    public function weaknesses(): string
    {
        return 'يوجد ضعف في بعض معايير السلامة والتوثيق.';
    }

    protected function percentage(): float
    {
        $potential = max(1, $this->calculator->potentialScore());

        return round(
            ($this->calculator->totalScore() / $potential) * 100,
            2
        );
    }
}