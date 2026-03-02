<?php
function normalize_username(string $u): string {
    return strtolower(trim($u));
}

function handle_register(): void {
    $body = read_json_body();
    $username = normalize_username((string)($body['username'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($username === '' || $password === '') json_error('username and password required', 422);
    if (strlen($username) < 3) json_error('username too short', 422);
    if (strlen($password) < 6) json_error('password too short', 422);
    if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) json_error('username invalid', 422);

    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) json_error('username already exists', 409);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users(username, password_hash, role) VALUES(:u, :h, :r) RETURNING id');
    $ins->execute([':u' => $username, ':h' => $hash, ':r' => 'user']);
    $userId = (int)$ins->fetchColumn();

    $token = make_token($userId);
    json_response(['user' => ['id' => $userId, 'username' => $username, 'role' => 'user'], 'token' => $token], 201);
}

function handle_login(): void {
    $body = read_json_body();
    $username = normalize_username((string)($body['username'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($username === '' || $password === '') json_error('username and password required', 422);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user) json_error('Invalid credentials', 401);
    if (!password_verify($password, $user['password_hash'])) json_error('Invalid credentials', 401);

    $userId = (int)$user['id'];
    $token = make_token($userId);
    json_response(['user' => ['id' => $userId, 'username' => $username, 'role' => $user['role']], 'token' => $token]);
}