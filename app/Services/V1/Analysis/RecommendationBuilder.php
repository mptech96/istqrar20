<?php

namespace App\Services\V1\Analysis;

class RecommendationBuilder
{
    public function toJson(): array
    {
        return [
            [
                'priority' => 'high',
                'action'   => 'إعادة تدريب الموظفين على السلامة',
                'deadline' => now()->addDays(30)->toDateString(),
            ]
        ];
    }
}