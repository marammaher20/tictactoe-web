<?php
require_once 'room_lib.php';
session_start();

$code = is_string($_GET['code'] ?? null) ? strtoupper($_GET['code']) : '';
$me = requireMember($code);
session_write_close(); // عشان الطلبات ما تنتظر بعضها

$since = isset($_GET['v']) ? (int)$_GET['v'] : -1;

try {
    $pdo = getConnection();
    $room = loadRoom($pdo, $code);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonFail('حصل خطأ بقاعدة البيانات');
}

// إذا ما تغيّر شي، نرد برد خفيف
if ((int)$room['version'] === $since) {
    jsonOut(['success' => true, 'changed' => false]);
}

jsonOut(roomState($room, $me));