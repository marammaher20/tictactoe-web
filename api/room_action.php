<?php
require_once 'room_lib.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail('طلب غير صالح');
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$code   = is_string($data['code'] ?? null) ? strtoupper($data['code']) : '';
$action = $data['action'] ?? '';

$me = requireMember($code);

// حماية CSRF
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    jsonFail('طلب غير صالح (CSRF)');
}
session_write_close();

try {
    $pdo  = getConnection();
    $room = loadRoom($pdo, $code);

    if ($action === 'move') {
        $cell = $data['cell'] ?? null;

        if (!is_int($cell) || $cell < 0 || $cell > 8) {
            jsonFail('خانة غير صحيحة');
        }
        if ($room['status'] !== 'playing') {
            jsonFail('الجولة غير جارية حالياً');
        }
        if ($room['turn'] !== $me) {
            jsonFail('مش دورك!');
        }
        if ($room['board'][$cell] !== '-') {
            jsonFail('الخانة مش فاضية');
        }

        $board = $room['board'];
        $board[$cell] = $me; // الرمز من السيرفر مش من المتصفح
        [$result, $line] = evaluateBoard($board);

        // الشرط بيضمن إنه الحالة ما تغيّرت بين القراءة والكتابة
        $where = "WHERE code = :c AND status = 'playing' AND turn = :me AND version = :v";

        if ($result === 'Continue') {
            $stmt = $pdo->prepare(
                "UPDATE rooms SET board = :b, turn = :t, version = version + 1 $where"
            );
            $stmt->execute([
                ':b'  => $board,
                ':t'  => ($me === 'X') ? 'O' : 'X',
                ':c'  => $code,
                ':me' => $me,
                ':v'  => $room['version'],
            ]);
        } else {
            $scoreCol = ['X' => 'score_x', 'O' => 'score_o', 'Draw' => 'draws'][$result];
            $stmt = $pdo->prepare(
                "UPDATE rooms
                 SET board = :b, status = 'finished', result = :r, win_line = :w,
                     $scoreCol = $scoreCol + 1, version = version + 1
                 $where"
            );
            $stmt->execute([
                ':b'  => $board,
                ':r'  => $result,
                ':w'  => implode(',', $line),
                ':c'  => $code,
                ':me' => $me,
                ':v'  => $room['version'],
            ]);
        }

        if ($stmt->rowCount() !== 1) {
            jsonFail('تغيّرت حالة اللعبة، جرّبي مرة ثانية');
        }

        // نحفظ نتيجة الجولة بجدول results (للوحة الصدارة)
        if ($result !== 'Continue') {
            try {
                $winner = ($result === 'X') ? $room['player_x']
                        : (($result === 'O') ? $room['player_o'] : null);
                $ins = $pdo->prepare(
                    'INSERT INTO results (player1, player2, result, winner_name)
                     VALUES (:p1, :p2, :r, :w)'
                );
                $ins->execute([
                    ':p1' => $room['player_x'],
                    ':p2' => $room['player_o'],
                    ':r'  => $result,
                    ':w'  => $winner,
                ]);
            } catch (PDOException $e) {
                error_log('results insert: ' . $e->getMessage());
            }
        }

    } elseif ($action === 'next') {
        $stmt = $pdo->prepare(
            "UPDATE rooms
             SET board = '---------', turn = 'X', status = 'playing',
                 result = NULL, win_line = NULL, version = version + 1
             WHERE code = :c AND status = 'finished'"
        );
        $stmt->execute([':c' => $code]);

    } else {
        jsonFail('أمر غير معروف');
    }

    $room = loadRoom($pdo, $code);
    jsonOut(roomState($room, $me));

} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonFail('حصل خطأ بقاعدة البيانات');
}