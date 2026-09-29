<?php
/** Endpoint JSON untuk chat: poll (GET) & send (POST). */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat_functions.php';

header('Content-Type: application/json; charset=utf-8');

function jsonOut($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!isLoggedIn()) {
    jsonOut(['error' => 'Belum login'], 401);
}
$me = currentUserId();
$roomId = (int) ($_REQUEST['room'] ?? 0);

if (!getRoomForUser($roomId, $me)) {
    jsonOut(['error' => 'Room tidak ditemukan'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        jsonOut(['error' => 'Sesi tidak valid, muat ulang halaman'], 419);
    }
    $msg = sendChatMessage($roomId, $me, $_POST['pesan'] ?? '');
    if (!$msg) {
        jsonOut(['error' => 'Pesan kosong atau terlalu panjang (maks 2000 karakter)'], 422);
    }
    jsonOut(['message' => chatMessageToJson($msg, $me)]);
}

// poll pesan baru
$after = (int) ($_GET['after'] ?? 0);
$rows = getRoomMessages($roomId, $after);
markRoomRead($roomId, $me);
jsonOut(['messages' => array_map(function ($m) use ($me) { return chatMessageToJson($m, $me); }, $rows)]);
