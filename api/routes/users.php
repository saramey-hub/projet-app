<?php
function handle_users_list(): void {
    $me = require_auth_user_id();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, username, role, created_at FROM users WHERE id <> :me ORDER BY username');
    $stmt->execute([':me' => $me]);
    $users = $stmt->fetchAll();

    json_response(['users' => $users]);
}