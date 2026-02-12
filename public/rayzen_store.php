<?php
/**
 * ReadyCRM - Rayzen Store API (Conversations + Messages)
 * Path: /public/rayzen_store.php
 */

session_start();
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/database.php';
require_once __DIR__ . '/../private/auth.php';
require_once __DIR__ . '/../private/functions.php';

header('Content-Type: application/json; charset=utf-8');

function out($arr, $code = 200){
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) out(['success'=>false, 'error'=>'unauthorized'], 401);
if (!hasPermission('view_dashboard')) out(['success'=>false, 'error'=>'forbidden'], 403);

$action = $_POST['action'] ?? '';
$csrf = $_POST['csrf'] ?? '';

if (!$action) out(['success'=>false, 'error'=>'no_action'], 400);
if (!verifyCSRFToken($csrf)) out(['success'=>false, 'error'=>'csrf_invalid'], 403);

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) out(['success'=>false, 'error'=>'invalid_user'], 401);

// Helpers
function hasTables(PDO $pdo){
    try{
        $pdo->query("SELECT 1 FROM ai_conversations LIMIT 1");
        $pdo->query("SELECT 1 FROM ai_messages LIMIT 1");
        return true;
    } catch(Throwable $e){
        return false;
    }
}

if ($action === 'ping') {
    $ok = hasTables($pdo);
    out(['success'=>$ok, 'tables'=>$ok ? 'ok' : 'missing']);
}

if (!hasTables($pdo)) {
    out(['success'=>false, 'error'=>'tables_missing'], 500);
}

try {

    if ($action === 'list_conversations') {
        $stmt = $pdo->prepare("
            SELECT id, title, last_preview, pinned, archived, created_at, updated_at
            FROM ai_conversations
            WHERE user_id = ? AND archived = 0
            ORDER BY pinned DESC, updated_at DESC
            LIMIT 80
        ");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        out(['success'=>true, 'conversations'=>$rows]);
    }

    if ($action === 'create_conversation') {
        $title = trim($_POST['title'] ?? 'مکالمه جدید');
        if ($title === '') $title = 'مکالمه جدید';

        $stmt = $pdo->prepare("
            INSERT INTO ai_conversations (user_id, title, last_preview, pinned, archived, created_at, updated_at)
            VALUES (?, ?, '', 0, 0, NOW(), NOW())
        ");
        $stmt->execute([$user_id, $title]);
        $id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT id, title, last_preview, pinned, archived, created_at, updated_at FROM ai_conversations WHERE id=? AND user_id=?");
        $stmt->execute([$id, $user_id]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);

        out(['success'=>true, 'conversation'=>$conv]);
    }

    if ($action === 'rename_conversation') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if ($id <= 0 || $title === '') out(['success'=>false, 'error'=>'invalid'], 400);

        $stmt = $pdo->prepare("UPDATE ai_conversations SET title=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->execute([$title, $id, $user_id]);

        out(['success'=>true]);
    }

    if ($action === 'set_pinned') {
        $id = (int)($_POST['id'] ?? 0);
        $pinned = (int)($_POST['pinned'] ?? 0);
        if ($id <= 0) out(['success'=>false, 'error'=>'invalid'], 400);

        $stmt = $pdo->prepare("UPDATE ai_conversations SET pinned=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->execute([$pinned ? 1 : 0, $id, $user_id]);

        out(['success'=>true]);
    }

    if ($action === 'delete_conversation') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) out(['success'=>false, 'error'=>'invalid'], 400);

        // delete messages then conversation
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM ai_messages WHERE conversation_id=?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM ai_conversations WHERE id=? AND user_id=?");
        $stmt->execute([$id, $user_id]);

        $pdo->commit();

        out(['success'=>true]);
    }

    if ($action === 'get_messages') {
        $id = (int)($_POST['id'] ?? 0);
        $limit = (int)($_POST['limit'] ?? 200);
        if ($id <= 0) out(['success'=>false, 'error'=>'invalid'], 400);
        if ($limit <= 0 || $limit > 500) $limit = 200;

        // ensure ownership
        $stmt = $pdo->prepare("SELECT id FROM ai_conversations WHERE id=? AND user_id=? LIMIT 1");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) out(['success'=>false, 'error'=>'not_found'], 404);

        $stmt = $pdo->prepare("
            SELECT role, content, created_at
            FROM ai_messages
            WHERE conversation_id = ?
            ORDER BY id ASC
            LIMIT $limit
        ");
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        out(['success'=>true, 'messages'=>$rows]);
    }

    if ($action === 'add_message') {
        $id = (int)($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $content = (string)($_POST['content'] ?? '');

        if ($id <= 0) out(['success'=>false, 'error'=>'invalid_id'], 400);
        if (!in_array($role, ['user','ai'], true)) out(['success'=>false, 'error'=>'invalid_role'], 400);
        if (trim($content) === '') out(['success'=>false, 'error'=>'empty'], 400);

        // ensure ownership
        $stmt = $pdo->prepare("SELECT id FROM ai_conversations WHERE id=? AND user_id=? LIMIT 1");
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) out(['success'=>false, 'error'=>'not_found'], 404);

        $stmt = $pdo->prepare("INSERT INTO ai_messages (conversation_id, role, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, $role, $content]);

        // update preview
        $preview = mb_substr($content, 0, 80);
        $stmt = $pdo->prepare("UPDATE ai_conversations SET last_preview=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->execute([$preview, $id, $user_id]);

        out(['success'=>true]);
    }

    out(['success'=>false, 'error'=>'unknown_action'], 400);

} catch (Throwable $e) {
    out(['success'=>false, 'error'=>'server_error'], 500);
}
