<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Controllers\ApiController;
use App\Controllers\WebController;
use App\Router;

$router = new Router();

$router->get('/', [WebController::class, 'home']);
$router->get('/api/health', [ApiController::class, 'health']);
$router->get('/api/topics', [ApiController::class, 'topics']);
$router->get('/api/grammar-topics', [ApiController::class, 'grammarTopics']);
$router->get('/api/prompt-preview', [ApiController::class, 'promptPreview']);
$router->post('/api/session/start', [ApiController::class, 'startSession']);
$router->post('/api/session/apply-filters', [ApiController::class, 'applySessionFilters']);
$router->post('/api/chat', [ApiController::class, 'chat']);
$router->post('/api/speech-to-text', [ApiController::class, 'speechToText']);
$router->post('/api/text-to-speech', [ApiController::class, 'textToSpeech']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
