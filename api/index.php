<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if ($path === '/login' && $method === 'POST') {
        require __DIR__ . '/routes/auth.php';
        handle_login();
    } elseif ($path === '/register' && $method === 'POST') {
        require __DIR__ . '/routes/auth.php';
        handle_register();
    } elseif ($path === '/users' && $method === 'GET') {
        require __DIR__ . '/routes/users.php';
        handle_users_list();
    } elseif ($path === '/conversations' && $method === 'GET') {
        require __DIR__ . '/routes/conversations.php';
        handle_conversations_list();
    } elseif ($path === '/messages' && $method === 'GET') {
        require __DIR__ . '/routes/messages.php';
        handle_messages_get();
    } elseif ($path === '/messages' && $method === 'POST') {
        require __DIR__ . '/routes/messages.php';
        handle_messages_send();
    } elseif (preg_match('#^/messages/(\d+)$#', $path, $m) && $method === 'DELETE') {
        require __DIR__ . '/routes/messages.php';
        handle_messages_delete((int)$m[1]);
    } else {
        json_error('Not found', 404);
    }
} catch (Throwable $e) {
    json_error('Server error', 500, $e->getMessage());
}