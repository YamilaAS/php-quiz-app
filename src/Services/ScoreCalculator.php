<?php
namespace App\Services;

class ScoreCalculator {
    public function calculateScore(int $correctAnswers, int$totalQuestions): float {
        if ($totalQuestions === 0) {
            return 0.0;
        }
        return ($correctAnswers / $totalQuestions) * 100;
    }

    public function getFeedbackMessage(float $score): string {
        if ($score === 100.0) { return "¡Excelente! Dominas perfectamente la temática."; } elseif ($score >= 70.0) {
            return "¡Muy bien! Tienes una buena base, pero repasa algunos conceptos.";
        } else {
            return "Necesitas repasar más. ¡Sigue practicando!";
        }
    }
}