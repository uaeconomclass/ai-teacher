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
$router->post('/api/chat', [ApiController::class, 'chat']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
