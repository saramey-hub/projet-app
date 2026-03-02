<?php
function handle_messages_get(): void {
    $me = require_auth_user_id();

    $with = (int)($_GET['with'] ?? 0);
    if ($with <= 0) json_error('with parameter required', 422);

    $sinceId = (int)($_GET['since_id'] ?? 0);

    $pdo = db();

    $sql = "
    SELECT id, sender_id, receiver_id, content, created_at, deleted_at
    FROM messages
    WHERE deleted_at IS NULL
      AND (
        (sender_id = :me AND receiver_id = :with)
        OR
        (sender_id = :with AND receiver_id = :me)
      )
  ";

    $params = [':me' => $me, ':with' => $with];

    if ($sinceId > 0) {
        $sql .= " AND id > :since_id";
        $params[':since_id'] = $sinceId;
    }

    $sql .= " ORDER BY id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    json_response(['messages' => $messages]);
}

function handle_messages_send(): void {
    $me = require_auth_user_id();
    $body = read_json_body();

    $to = (int)($body['to'] ?? 0);
    $content = trim((string)($body['content'] ?? ''));

    if ($to <= 0 || $content === '') json_error('to and content required', 422);

    $pdo = db();

    $chk = $pdo->prepare('SELECT id FROM users WHERE id = :id');
    $chk->execute([':id' => $to]);
    if (!$chk->fetch()) json_error('Recipient not found', 404);

    $ins = $pdo->prepare('INSERT INTO messages(sender_id, receiver_id, content) VALUES(:s, :r, :c) RETURNING id');
    $ins->execute([':s' => $me, ':r' => $to, ':c' => $content]);
    $id = (int)$ins->fetchColumn();

    json_response(['message' => ['id' => $id]], 201);
}

function handle_messages_delete(int $messageId): void {
    $me = require_auth_user_id();
    $pdo = db();

    $upd = $pdo->prepare('UPDATE messages SET deleted_at = NOW() WHERE id = :id AND sender_id = :me AND deleted_at IS NULL');
    $upd->execute([':id' => $messageId, ':me' => $me]);

    if ($upd->rowCount() === 0) json_error('Not found or not allowed', 404);
    json_response(['ok' => true]);
}