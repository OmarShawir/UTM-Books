<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimit;
use App\Repository\UserRepository;
use App\Repository\BookRepository;
use App\Repository\AuditLog;

$userRepo  = new UserRepository($pdo);
$bookRepo  = new BookRepository($pdo);
$auditLog  = new AuditLog($pdo);

$authCtrl  = new AuthController($userRepo, $auditLog);
$bookCtrl  = new BookController($bookRepo, $auditLog);
$authMw    = new AuthMiddleware();

// Health check
$app->get('/', function ($req, $res) {
    $res->getBody()->write(json_encode(['status' => 'ok', 'api' => 'Books API v1']));
    return $res->withHeader('Content-Type', 'application/json');
});

// Auth routes
$loginMw = new RateLimit(
    (int)($_ENV['LOGIN_RATE_LIMIT'] ?? 5),
    (int)($_ENV['LOGIN_WINDOW_SECONDS'] ?? 60),
    'login'
);
$app->post('/auth/register', [$authCtrl, 'register']);
$app->post('/auth/login',    [$authCtrl, 'login'])->add($loginMw);

// Protected routes
$app->group('', function ($group) use ($bookCtrl, $authCtrl) {
    $group->get('/auth/me',              [$authCtrl, 'me']);
    $group->get('/api/books',            [$bookCtrl, 'index']);
    $group->get('/api/books/{id}',       [$bookCtrl, 'show']);
    $group->post('/api/books',           [$bookCtrl, 'create']);
    $group->put('/api/books/{id}',       [$bookCtrl, 'update']);
    $group->delete('/api/books/{id}',    [$bookCtrl, 'delete']);
})->add($authMw);

// CORS preflight
$app->options('/{routes:.+}', function ($req, $res) {
    return $res;
});
