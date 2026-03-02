<?php
function handle_conversations_list(): void {
    $me = require_auth_user_id();
    $pdo = db();

    // Dernier message par contact (l'autre utilisateur dans la conversation)
    $sql = "
    WITH last_msgs AS (
      SELECT DISTINCT ON (other_user_id)
        other_user_id,
        id AS last_message_id,
        sender_id AS last_sender_id,
        receiver_id AS last_receiver_id,
        content AS last_content,
        created_at AS last_created_at
      FROM (
        SELECT
          m.*,
          CASE WHEN m.sender_id = :me THEN m.receiver_id ELSE m.sender_id END AS other_user_id
        FROM messages m
        WHERE m.deleted_at IS NULL
          AND (m.sender_id = :me OR m.receiver_id = :me)
      ) x
      ORDER BY other_user_id, id DESC
    )
    SELECT
      u.id,
      u.username,
      u.role,
      u.created_at,
      lm.last_message_id,
      lm.last_sender_id,
      lm.last_receiver_id,
      lm.last_content,
      lm.last_created_at
    FROM users u
    LEFT JOIN last_msgs lm ON lm.other_user_id = u.id
    WHERE u.id <> :me
    ORDER BY COALESCE(lm.last_message_id, 0) DESC, u.username ASC
  ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':me' => $me]);
    $rows = $stmt->fetchAll();

    json_response(['conversations' => $rows]);
}