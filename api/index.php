<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/app/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\ConversationsController;
use App\Controllers\MessagesController;
use App\Controllers\UsersController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

// Static uploads (no auth)
if ($method === 'GET' && str_starts_with($path, '/uploads/')) {
    $filePath = __DIR__ . $path;

    if (!is_file($filePath)) {
        Response::error('Not found', 404);
    }

    $mime = mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($filePath));
    readfile($filePath);
    exit;
}

try {
    $req = new Request();
    $router = new Router();

    $authController = new AuthController();
    $usersController = new UsersController();
    $conversationsController = new ConversationsController();
    $messagesController = new MessagesController();

    $router->add('POST', '/login', fn(Request $r) => $authController->login($r));
    $router->add('POST', '/register', fn(Request $r) => $authController->register($r));

    $router->add('GET', '/users', fn(Request $r) => $usersController->list($r));
    $router->add('GET', '/conversations', fn(Request $r) => $conversationsController->list($r));

    $router->add('GET', '/messages', fn(Request $r) => $messagesController->get($r));
    $router->add('POST', '/messages', fn(Request $r) => $messagesController->send($r));
    $router->add('POST', '/messages/image', fn(Request $r) => $messagesController->sendImage($r));
    $router->add('DELETE', '#^/messages/(\d+)$#', fn(Request $r, array $m) => $messagesController->delete($r, (int) $m[1]));

    $router->dispatch($req);
} catch (Throwable $e) {
    Response::error('Server error', 500, $e->getMessage());
}