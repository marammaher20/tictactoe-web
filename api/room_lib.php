<?php
require_once __DIR__ . '/../config/db.php';

const WIN_LINES = [
    [0, 1, 2], [3, 4, 5], [6, 7, 8],
    [0, 3, 6], [1, 4, 7], [2, 5, 8],
    [0, 4, 8], [2, 4, 6],
];

function jsonOut(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonFail(string $msg): void
{
    jsonOut(['success' => false, 'message' => $msg]);
}

// الغرفة لازم تكون من الغرف اللي إنتِ داخلة فيها (حسب الـ Session)
function requireMember(string $code): string
{
    if (!preg_match('/^[A-Z2-9]{5}$/', $code) || !isset($_SESSION['online'][$code])) {
        jsonFail('غير مصرّح لك بهالغرفة');
    }
    return $_SESSION['online'][$code]; // 'X' أو 'O'
}

function loadRoom(PDO $pdo, string $code): array
{
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = :c');
    $stmt->execute([':c' => $code]);
    $room = $stmt->fetch();
    if (!$room) {
        jsonFail('الغرفة غير موجودة');
    }
    return $room;
}

// ترجع [X أو O أو Draw أو Continue, خط الفوز]
function evaluateBoard(string $b): array
{
    foreach (WIN_LINES as $l) {
        $a = $b[$l[0]];
        if ($a !== '-' && $a === $b[$l[1]] && $a === $b[$l[2]]) {
            return [$a, $l];
        }
    }
    if (strpos($b, '-') === false) {
        return ['Draw', []];
    }
    return ['Continue', []];
}

function roomState(array $room, string $me): array
{
    $winLine = ($room['win_line'] !== null && $room['win_line'] !== '')
        ? array_map('intval', explode(',', $room['win_line']))
        : [];

    return [
        'success' => true,
        'changed' => true,
        'me'      => $me,
        'version' => (int)$room['version'],
        'status'  => $room['status'],
        'turn'    => $room['turn'],
        'board'   => str_split($room['board']),
        'result'  => $room['result'],
        'winLine' => $winLine,
        'players' => [
            'X' => ['name' => $room['player_x'], 'avatar' => $room['avatar_x']],
            'O' => ['name' => $room['player_o'], 'avatar' => $room['avatar_o']],
        ],
        'scores'  => [
            'X'     => (int)$room['score_x'],
            'O'     => (int)$room['score_o'],
            'draws' => (int)$room['draws'],
        ],
    ];
}