<?php

namespace App\Repositories;

use App\Models\Question;

class QuestionRepository {
    private string $dataPath;

    public function __construct(string $dataPath = __DIR__ . '/../../data/') {
        $this->dataPath = $dataPath;
    }

    public function getAvailableTopics(): array {
        $files = glob($this->dataPath . '*.json');
        $topics = [];
        foreach ($files as $file) {
            $topics[] = pathinfo($file, PATHINFO_FILENAME);
        }
        return $topics;
    }

   public function getQuestionsByTopic(string $topic, ?int $limit = null): array {
        $file = $this->dataPath . strtolower($topic) . '.json';
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        $questions = [];

        foreach ($data as $item) {
            $question = new Question(
                $item['id'],
                $item['question'],
                $item['options'],
                $item['correct_answer'],
                $item['explanation']
            );
            $question->shuffleOptions();
            $questions[] = $question;
        }

        // Mezclar el orden general de las preguntas
        shuffle($questions);

        // Si se especifica un límite y es menor al total, recortamos el array
        if ($limit !== null && $limit > 0 && $limit < count($questions)) {
            $questions = array_slice($questions, 0, $limit);
        }

        return $questions;
    }

    public function saveQuestion(string $topic, array $questionData): bool {
        $filename = $this->dataPath . strtolower(trim($topic)) . '.json';
        
        $questions = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $questions = json_decode($content, true) ?? [];
        }

        $newId = count($questions) > 0 ? max(array_column($questions, 'id')) + 1 : 1;

        $newQuestion = [
            "id" => $newId,
            "question" => $questionData['question'],
            "options" => $questionData['options'],
            "correct_answer" => (int)$questionData['correct_answer'],
            "explanation" => $questionData['explanation']
        ];

        $questions[] = $newQuestion;

        return file_put_contents($filename, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
}