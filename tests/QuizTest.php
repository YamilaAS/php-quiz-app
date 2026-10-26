<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\Question;
use App\Services\ScoreCalculator;
use App\Repositories\QuestionRepository;

class QuizTest extends TestCase {
    public function testQuestionValidation() {
        $question = new Question(
            1, 
            "¿Cuánto es 2+2?", 
            ["3", "4", "5"], 
            1, // "4" es el índice 1
            "2 + 2 es igual a 4."
        );

        $this->assertTrue($question->validateAnswer(1));
        $this->assertFalse($question->validateAnswer(0));
    }

    public function testShuffleOptionsKeepsCorrectAnswerValid() {
        $question = new Question(
            2, 
            "¿Capital de Francia?", 
            ["Londres", "París", "Berlín"], 
            1, 
            "París es la capital."
        );

        $correctTextBefore = $question->getOptions()[$question->getCorrectAnswer()];

        for ($i = 0; $i < 10; $i++) {
            $question->shuffleOptions();
            $newCorrectIndex = $question->getCorrectAnswer();
            $correctTextAfter = $question->getOptions()[$newCorrectIndex];

            $this->assertEquals($correctTextBefore, $correctTextAfter);
            $this->assertTrue($question->validateAnswer($newCorrectIndex));
        }
    }

    public function testScoreCalculation() {
        $calculator = new ScoreCalculator();
        $score = $calculator->calculateScore(3, 4);
        
        $this->assertEquals(75.0, $score);
        $this->assertStringContainsString("buena base", $calculator->getFeedbackMessage($score));
    }

    public function testRepositoryLimitAndRandomization() {
        // Usamos una ruta temporal o apuntamos a la carpeta data actual
        $repo = new QuestionRepository();
        
        // Suponiendo que 'php' tiene al menos 2 preguntas
        $questions = $repo->getQuestionsByTopic('php', 1);

        // Verificamos que el límite de 1 se cumpla estrictamente
        $this->assertCount(1, $questions);
        $this->assertInstanceOf(Question::class, $questions[0]);
    }
}