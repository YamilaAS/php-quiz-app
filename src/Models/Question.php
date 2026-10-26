<?php

namespace App\Models;

class Question {
    private int $id;
    private string $question;
    private array $options;
    private int $correctAnswer;
    private string $explanation;

    public function __construct(int $id, string $question, array $options, int $correctAnswer, string $explanation) {
        $this->id = $id;
        $this->question = $question;
        $this->options = $options;
        $this->correctAnswer = $correctAnswer;
        $this->explanation = $explanation;
    }

    public function getId(): int { return $this->id; }
    public function getQuestion(): string { return $this->question; }
    public function getOptions(): array { return $this->options; }
    public function getCorrectAnswer(): int { return $this->correctAnswer; }
    public function getExplanation(): string { return $this->explanation; }

    public function validateAnswer(int $selectedIndex): bool {
        return $selectedIndex === $this->correctAnswer;
    }

    /**
     * Mezcla las opciones al azar manteniendo la sincronía con la respuesta correcta.
     */
    public function shuffleOptions(): void {
        $assoc = [];
        foreach ($this->options as $idx => $opt) {
            $assoc[] = [
                'text' => $opt,
                'is_correct' => ($idx === $this->correctAnswer)
            ];
        }

        shuffle($assoc);

        $newOptions = [];
        $newCorrectAnswer = 0;
        foreach ($assoc as $newIdx => $item) {
            $newOptions[] = $item['text'];
            if ($item['is_correct']) {
                $newCorrectAnswer = $newIdx;
            }
        }

        $this->options = $newOptions;
        $this->correctAnswer = $newCorrectAnswer;
    }
}