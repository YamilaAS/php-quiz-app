<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Repositories\QuestionRepository;
use App\Services\ScoreCalculator;

$repo = new QuestionRepository();
$calculator = new ScoreCalculator();

echo "========================================\n";
echo "    QUIZ DE ESTUDIO - CONSOLA PHP       \n";
echo "========================================\n\n";

$topics = $repo->getAvailableTopics();
if (empty($topics)) {
    echo "No se encontraron temáticas disponibles.\n";
    exit;
}

echo "Temáticas disponibles:\n";
foreach ($topics as $index => $topic) {
    echo "[" . ($index + 1) . "] " . ucfirst($topic) . "\n";
}

echo "\nElige una temática (número): ";
$choice = trim(fgets(STDIN));
$selectedIndex = (int)$choice - 1;

if (!isset($topics[$selectedIndex])) {
    echo "Opción inválida.\n";
    exit;
}

$selectedTopic = $topics[$selectedIndex];

// Preguntar la cantidad de preguntas
echo "Cantidad de preguntas a responder (ej: 5, 10, 20 o presiona Enter para todas): ";
$limitInput = trim(fgets(STDIN));
$limit = is_numeric($limitInput) ? (int)$limitInput : null;

$questions = $repo->getQuestionsByTopic($selectedTopic, $limit);

$correctCount = 0;
$total = count($questions);

if ($total === 0) {
    echo "No hay preguntas en esta temática.\n";
    exit;
}

foreach ($questions as $i => $q) {
    echo "\n----------------------------------------\n";
    echo "Pregunta " . ($i + 1) . " de $total:\n";
    echo $q->getQuestion() . "\n\n";

    $options = $q->getOptions();
    foreach ($options as $optIndex => $option) {
        echo "  [$optIndex] $option\n";
    }

    echo "\nTu respuesta (número): ";
    $answer = trim(fgets(STDIN));

    if ($q->validateAnswer((int)$answer)) {
        echo "✅ ¡Correcto!\n";
        $correctCount++;
    } else {
        echo "❌ Incorrecto.\n";
    }
    echo "💡 Explicación: " . $q->getExplanation() . "\n";
}

$score = $calculator->calculateScore($correctCount, $total);
echo "\n========================================\n";
echo "RESULTADOS FINALES\n";
echo "Aciertos: $correctCount de $total (" . number_format($score, 2) . "%)\n";
echo "Mensaje: " . $calculator->getFeedbackMessage($score) . "\n";
echo "========================================\n";