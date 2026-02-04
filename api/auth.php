<?php
$config = require __DIR__ . '/config.php';

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400, $extra = null): void {
    $payload = ['error' => $message];
    if ($extra !== null) $payload['details'] = $extra;
    json_response($payload, $status);
}

function make_token(int $userId): string {
    global $config;
    $ts = time();
    $payload = $userId . '.' . $ts;
    $sig = hash_hmac('sha256', $payload, $config['secret']);
    return base64_encode($payload . '.' . $sig);
}

function verify_token(?string $token): ?int {
    global $config;
    if (!$token) return null;

    $decoded = base64_decode($token, true);
    if ($decoded === false) return null;

    $parts = explode('.', $decoded);
    if (count($parts) !== 3) return null;

    [$userId, $ts, $sig] = $parts;
    if (!ctype_digit($userId) || !ctype_digit($ts)) return null;

    if ((int)$ts < time() - 7*24*3600) return null;

    $payload = $userId . '.' . $ts;
    $expected = hash_hmac('sha256', $payload, $config['secret']);
    if (!hash_equals($expected, $sig)) return null;

    return (int)$userId;
}

function require_auth_user_id(): int {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if (!$auth || !preg_match('/Bearer\s+(.+)/', $auth, $m)) {
        json_error('Missing Authorization', 401);
    }
    $userId = verify_token($m[1]);
    if (!$userId) json_error('Invalid token', 401);
    return $userId;
}