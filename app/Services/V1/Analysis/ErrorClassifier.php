<?php

namespace App\Services\V1\Analysis;

use App\Models\V1\Visit;

class ErrorClassifier
{
    public function __construct(private Visit $visit) {}

    public function critical(): int
    {
        return $this->visit->answers->filter(function ($a) {
            return $a->question->is_critical && $a->score < ($a->question->passing_score ?? 1);
        })->count();
    }

    public function byCriteria(): array
    {
        return $this->visit->answers
            ->groupBy(fn($a) => optional($a->question->criteria)->name)
            ->map(fn($group) => $group->count())
            ->toArray();
    }
}