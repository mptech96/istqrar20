<?php

namespace App\Services\V1\Analysis;

use App\Models\V1\Visit;
use App\Models\V1\VisitAnalysis;

class VisitAnalysisService
{
    public function generate(int $visitId): void
    {
        $visit = Visit::with([
            'answers.question.criteria',
            'branch.company'
        ])->findOrFail($visitId);

        $calculator = new ScoreCalculator($visit);
        $errors     = new ErrorClassifier($visit);
        $text       = new TextAnalysisBuilder($visit, $calculator, $errors);
        $reco       = new RecommendationBuilder($visit, $errors);

        VisitAnalysis::updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'branch_id'           => $visit->branch_id,
                'company_id'          => $visit->branch->company_id,
                'analysis_type'       => 'automatic',
                'analysis_date'       => now(),
                'total_questions'     => $calculator->totalQuestions(),
                'answered_questions'  => $calculator->answeredQuestions(),
                'critical_errors'     => $errors->critical(),
                'total_score'         => $calculator->totalScore(),
                'lost_points'         => $calculator->lostPoints(),
                'summary'             => $text->summary(),
                'strengths'           => $text->strengths(),
                'weaknesses'          => $text->weaknesses(),
                'recommendations'     => $reco->toJson(),
            ]
        );
    }
}