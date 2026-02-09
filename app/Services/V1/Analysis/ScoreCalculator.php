<?php

namespace App\Services\V1\Analysis;

class ScoreCalculator
{
    public function __construct(private Visit $visit) {}

    public function totalQuestions(): int
    {
        return $this->visit->checklist->questions()->count();
    }

    public function answeredQuestions(): int
    {
        return $this->visit->answers()->count();
    }

    public function totalScore(): float
    {
        return $this->visit->answers->sum(function ($answer) {
            return $answer->score * $answer->question->weight;
        });
    }

    public function potentialScore(): float
    {
        return $this->visit->answers->sum(function ($answer) {
            return $answer->max_score * $answer->question->weight;
        });
    }

    public function lostPoints(): float
    {
        return $this->potentialScore() - $this->totalScore();
    }
}