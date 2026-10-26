<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\QuestionRepository;
use App\Services\ScoreCalculator;

$repo = new QuestionRepository();
$calculator = new ScoreCalculator();
$topics = $repo->getAvailableTopics();

$action = $_GET['action'] ?? 'quiz';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_question'])) {
    $topic = trim($_POST['new_topic'] ?? '');
    if (empty($topic)) {
        $topic = trim($_POST['existing_topic'] ?? 'general');
    }

    $questionData = [
        'question' => $_POST['question'] ?? '',
        'options' => [
            $_POST['option_0'] ?? '',
            $_POST['option_1'] ?? '',
            $_POST['option_2'] ?? '',
            $_POST['option_3'] ?? ''
        ],
        'correct_answer' => $_POST['correct_answer'] ?? 0,
        'explanation' => $_POST['explanation'] ?? ''
    ];

    if (!empty($questionData['question']) && !empty($topic)) {
        $repo->saveQuestion($topic, $questionData);
        $message = "¡Pregunta agregada con éxito a la temática '$topic'!";
        $topics = $repo->getAvailableTopics();
    } else {
        $error = "Por favor, completa los campos obligatorios.";
    }
}

$selectedTopic = $_GET['topic'] ?? ($topics[0] ?? null);
if (!in_array($selectedTopic, $topics)) {
    $selectedTopic = $topics[0] ?? null;
}

$limit = isset($_GET['limit']) && $_GET['limit'] !== 'all' ? (int)$_GET['limit'] : null;
$questions = $selectedTopic ? $repo->getQuestionsByTopic($selectedTopic, $limit) : [];

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz']);
$score = 0;
$correctCount = 0;
$results = [];

if ($submitted) {
    $total = count($questions);
    foreach ($questions as $index => $q) {
        $userAnswer = isset($_POST['q_' . $index]) ? (int)$_POST['q_' . $index] : null;
        $isCorrect = $userAnswer !== null && $q->validateAnswer($userAnswer);
        if ($isCorrect) $correctCount++;
        
        $results[$index] = [
            'correct' => $isCorrect,
            'user_answer' => $userAnswer,
            'correct_answer' => $q->getCorrectAnswer(),
            'explanation' => $q->getExplanation()
        ];
    }
    $score = $calculator->calculateScore($correctCount, $total);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Quiz de Estudio - Interfaz Web</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4 text-center">🎓 Quiz de Estudio Técnico</h1>

        <ul class="nav nav-pills justify-content-center mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $action === 'quiz' ? 'active' : '' ?>" href="index.php?action=quiz">📝 Resolver Quiz</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $action === 'upload' ? 'active' : '' ?>" href="index.php?action=upload">➕ Cargar Material</a>
            </li>
        </ul>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($action === 'upload'): ?>
            <div class="card shadow-sm p-4">
                <h3 class="mb-3">Agregar Nueva Pregunta</h3>
                <form method="POST" action="index.php?action=upload">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Temática Existente:</label>
                        <select name="existing_topic" class="form-select">
                            <?php foreach ($topics as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= ucfirst(htmlspecialchars($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">O crear una nueva temática (opcional):</label>
                        <input type="text" name="new_topic" class="form-control" placeholder="Ej: javascript, bases-de-datos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pregunta:</label>
                        <textarea name="question" class="form-control" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Opciones de respuesta:</label>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text">Opción <?= $i ?></span>
                                <input type="text" name="option_<?= $i ?>" class="form-control" required>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Índice de la Respuesta Correcta (0 al 3):</label>
                        <select name="correct_answer" class="form-select">
                            <option value="0">Opción 0</option>
                            <option value="1">Opción 1</option>
                            <option value="2">Opción 2</option>
                            <option value="3">Opción 3</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Explicación breve:</label>
                        <textarea name="explanation" class="form-control" rows="2" required></textarea>
                    </div>

                    <button type="submit" name="save_question" class="btn btn-primary w-100">Guardar Pregunta</button>
                </form>
            </div>

        <?php else: ?>
            <form method="GET" class="card p-3 mb-4 shadow-sm">
                <input type="hidden" name="action" value="quiz">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="topic" class="form-label fw-bold">Temática:</label>
                        <select name="topic" id="topic" class="form-select">
                            <?php foreach ($topics as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $selectedTopic === $t ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($t)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="limit" class="form-label fw-bold">Cantidad de Preguntas:</label>
                        <select name="limit" id="limit" class="form-select">
                            <option value="5" <?= $limit === 5 ? 'selected' : '' ?>>5 preguntas</option>
                            <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10 preguntas</option>
                            <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>20 preguntas</option>
                            <option value="all" <?= $limit === null ? 'selected' : '' ?>>Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">Cargar</button>
                    </div>
                </div>
            </form>

            <?php if ($submitted): ?>
                <div class="alert alert-info shadow-sm">
                    <h3>Resultados</h3>
                    <p>Aciertos: <strong><?= $correctCount ?> / <?= count($questions) ?></strong> (<?= number_format($score, 2) ?>%)</p>
                    <p><em><?= htmlspecialchars($calculator->getFeedbackMessage($score)) ?></em></p>
                    <a href="index.php?action=quiz&topic=<?= htmlspecialchars($selectedTopic) ?>&limit=<?= $limit ?? 'all' ?>" class="btn btn-primary">Intentar de nuevo</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($questions) && !$submitted): ?>
                <div class="card p-3 mb-4 shadow-sm bg-white sticky-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-secondary" id="progress-text">Progreso: 0% respondidas</span>
                        <span class="badge bg-danger fs-6" id="timer">⏱️ Tiempo restante: 03:00</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div id="quiz-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            <?php endif; ?>

            <form id="quiz-form" method="POST" action="index.php?action=quiz&topic=<?= htmlspecialchars($selectedTopic) ?>&limit=<?= $limit ?? 'all' ?>">
                <input type="hidden" name="topic" value="<?= htmlspecialchars($selectedTopic) ?>">
                
                <?php foreach ($questions as $index => $q): ?>
                    <div class="card mb-3 shadow-sm question-card">
                        <div class="card-body">
                            <h5 class="card-title">Pregunta <?= $index + 1 ?>: <?= htmlspecialchars($q->getQuestion()) ?></h5>
                            <?php foreach ($q->getOptions() as $optIdx => $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input answer-radio" type="radio" name="q_<?= $index ?>" value="<?= $optIdx ?>" id="q_<?= $index ?>_opt_<?= $optIdx ?>" required <?= $submitted && isset($results[$index]) && $results[$index]['user_answer'] === $optIdx ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="q_<?= $index ?>_opt_<?= $optIdx ?>">
                                        <?= htmlspecialchars($opt) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($submitted && isset($results[$index])): ?>
                                <div class="mt-2 p-2 rounded <?= $results[$index]['correct'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                    <strong><?= $results[$index]['correct'] ? '¡Correcto!' : 'Incorrecto.' ?></strong> 
                                    <br><small>💡 <?= htmlspecialchars($q->getExplanation()) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?><!-- Corregido a endforeach -->

                <?php if (!empty($questions) && !$submitted): ?>
                    <button type="submit" name="submit_quiz" class="btn btn-success btn-lg w-100 shadow">Enviar Respuestas</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <script>
        <?php if (!empty($questions) && !$submitted): ?>
        const totalQuestions = <?= count($questions) ?>;
        let timeLeft = Math.max(120, totalQuestions * 30); 

        const timerElement = document.getElementById('timer');
        const progressBar = document.getElementById('quiz-progress-bar');
        const progressText = document.getElementById('progress-text');
        const quizForm = document.getElementById('quiz-form');
        const radioInputs = document.querySelectorAll('.answer-radio');

        function updateProgress() {
            const totalGroups = new Set();
            radioInputs.forEach(input => {
                if (input.checked) {
                    totalGroups.add(input.name);
                }
            });
            const answeredCount = totalGroups.size;
            const percentage = Math.round((answeredCount / totalQuestions) * 100);
            
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressText.innerText = `Progreso: ${answeredCount} de ${totalQuestions} respondidas (${percentage}%)`;
        }

        radioInputs.forEach(input => {
            input.addEventListener('change', updateProgress);
        });

        const timerInterval = setInterval(() => {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;

            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            timerElement.innerText = `⏱️ Tiempo restante: ${minutes}:${seconds}`;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('¡Se acabó el tiempo! El cuestionario se enviará automáticamente.');
                const timeInput = document.createElement('input');
                timeInput.type = 'hidden';
                timeInput.name = 'submit_quiz';
                timeInput.value = '1';
                quizForm.appendChild(timeInput);
                quizForm.submit();
            }
            timeLeft--;
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>