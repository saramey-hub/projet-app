<?php
function handle_auth(): void {
    $body = read_json_body();
    $username = trim((string)($body['username'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($username === '' || $password === '') {
        json_error('username and password required', 422);
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users(username, password_hash, role) VALUES(:u, :h, :r) RETURNING id');
        $ins->execute([':u' => $username, ':h' => $hash, ':r' => 'user']);
        $userId = (int)$ins->fetchColumn();

        $token = make_token($userId);
        json_response(['user' => ['id' => $userId, 'username' => $username, 'role' => 'user'], 'token' => $token], 201);
    }

    if (!password_verify($password, $user['password_hash'])) {
        json_error('Invalid credentials', 401);
    }

    $userId = (int)$user['id'];
    $token = make_token($userId);
    json_response(['user' => ['id' => $userId, 'username' => $username, 'role' => $user['role']], 'token' => $token]);
}