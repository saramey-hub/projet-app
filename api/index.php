<?php
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// si tu lances le serveur sur api/ avec php -S, l’URL sera /auth, /users, etc.
try {
    if ($path === '/auth' && $method === 'POST') {
        require __DIR__ . '/routes/auth.php';
        handle_auth();
    } elseif ($path === '/users' && $method === 'GET') {
        require __DIR__ . '/routes/users.php';
        handle_users_list();
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