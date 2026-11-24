<?php

namespace App\Services\V1\Scoring;

use App\Models\V1\Checklist;
use App\Models\V1\Visit;
use App\Models\V1\Question;
use Illuminate\Support\Collection;

class VisitScoringService
{
    private const NON_SCORING_TYPES = ['text', 'date', 'file'];

    public function activeQuestions(Checklist $checklist): Collection
    {
        return $checklist->questions()
            ->where('is_active', true)
            ->with('criteria')
            ->orderBy('sort_order')
            ->get();
    }

    public function scoringQuestions(Checklist $checklist): Collection
    {
        return $this->activeQuestions($checklist)
            ->reject(fn ($question) => in_array($question->type, self::NON_SCORING_TYPES, true))
            ->values();
    }

    public function maximumAchievableScore(Question $question): int
    {
        if (in_array($question->type, self::NON_SCORING_TYPES, true)) {
            return 0;
        }

        if ($question->type === 'multiple_choice') {
            $options = $question->getOptionsArray();
            $maximumOptionScore = collect($options)->max(function ($option) {
                return is_array($option) ? (float) ($option['score'] ?? 0) : 0;
            });

            return (int) min((float) $question->max_score, max(0, (float) ($maximumOptionScore ?? 0)));
        }

        return (int) $question->max_score;
    }

    public function checklistScore(Checklist $checklist): array
    {
        $availableScore = (int) $this->scoringQuestions($checklist)
            ->sum(fn ($question) => $this->maximumAchievableScore($question));
        $requiredScore = (int) $checklist->total_score;

        return [
            'required_score' => $requiredScore,
            'available_score' => $availableScore,
            'shortfall' => max(0, $requiredScore - $availableScore),
            'is_valid' => $availableScore >= $requiredScore,
        ];
    }

    public function calculate(Visit $visit): array
    {
        $checklist = $visit->checklist;
        if (!$checklist) {
            return [
                'total_questions' => 0,
                'answered_questions' => 0,
                'total_score' => 0.0,
                'max_possible_score' => 0,
                'percentage' => 0.0,
                'category' => 'ضعيف',
                'questions' => collect(),
                'scoring_questions' => collect(),
                'answers' => collect(),
            ];
        }

        $questions = $this->activeQuestions($checklist);
        $scoringQuestions = $questions
            ->reject(fn ($question) => in_array($question->type, self::NON_SCORING_TYPES, true))
            ->values();

        // Include answers to soft-deleted/inactive questions for historical score
        // integrity, but only when the question belongs to this visit's checklist.
        $answers = $visit->answers()
            ->with(['question' => fn ($query) => $query->withoutGlobalScopes()->withTrashed()])
            ->get()
            ->filter(fn ($answer) => $answer->question
                && (int) $answer->question->checklist_id === (int) $visit->checklist_id)
            ->keyBy('question_id');

        // The visit stores its maximum score when it is scheduled. Keep that
        // denominator stable even if checklist settings are edited later.
        $calculatedMaximum = (int) $scoringQuestions
            ->sum(fn ($question) => $this->maximumAchievableScore($question));
        $maxPossibleScore = (int) ($visit->max_possible_score > 0
            ? $visit->max_possible_score
            : $calculatedMaximum);

        $totalScore = (float) $answers->sum(function ($answer) {
            if (!$answer->question || in_array($answer->question->type, self::NON_SCORING_TYPES, true)) {
                return 0;
            }

            $answerMaximum = $answer->max_score !== null
                ? (float) $answer->max_score
                : (float) $this->maximumAchievableScore($answer->question);

            return min($answerMaximum, max(0, (float) $answer->score));
        });

        $percentage = $maxPossibleScore > 0
            ? round(min(100, ($totalScore / $maxPossibleScore) * 100), 2)
            : 0.0;

        return [
            'total_questions' => max($questions->count(), $answers->count()),
            'answered_questions' => $answers->filter(fn ($answer) => $answer->answer_value !== null && trim((string) $answer->answer_value) !== '')->count(),
            'total_score' => round($totalScore, 2),
            'max_possible_score' => $maxPossibleScore,
            'percentage' => $percentage,
            'category' => $this->categoryFor($percentage),
            'questions' => $questions,
            'scoring_questions' => $scoringQuestions,
            'answers' => $answers,
        ];
    }

    public function completionIssues(Visit $visit): array
    {
        $checklist = $visit->checklist;
        if (!$checklist) {
            return [
                'missing_questions' => [],
                'missing_attachments' => [],
            ];
        }

        $questions = $this->activeQuestions($checklist);
        $answers = $visit->answers()
            ->whereIn('question_id', $questions->pluck('id'))
            ->with('attachments')
            ->get()
            ->keyBy('question_id');

        $missingQuestions = [];
        $missingAttachments = [];

        foreach ($questions as $question) {
            $answer = $answers->get($question->id);

            if ($question->is_required && (!$answer || trim((string) $answer->answer_value) === '')) {
                $missingQuestions[] = [
                    'id' => $question->id,
                    'question' => $question->question_text,
                ];
                continue;
            }

            $attachmentRequired = $question->has_attachment || $question->type === 'file';
            if ($answer && $attachmentRequired && $answer->attachments->isEmpty()) {
                $missingAttachments[] = [
                    'id' => $question->id,
                    'question' => $question->question_text,
                ];
            }
        }

        return [
            'missing_questions' => $missingQuestions,
            'missing_attachments' => $missingAttachments,
        ];
    }

    private function categoryFor(float $percentage): string
    {
        if ($percentage >= 90) return 'ممتاز';
        if ($percentage >= 80) return 'جيد جدا';
        if ($percentage >= 70) return 'جيد';
        if ($percentage >= 60) return 'مقبول';

        return 'ضعيف';
    }
}
