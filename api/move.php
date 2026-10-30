<?php
require_once '../classes/Board.php';
require_once '../classes/Game.php';
require_once '../config/db.php';
require_once '../config/csrf.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

function fail(string $message): void
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// حماية CSRF: لازم POST ومعه التوكن الصحيح
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    fail('طلب غير صالح (CSRF)');
}

$data = json_decode(file_get_contents('php://input'), true);
$row = $data['row'] ?? null;
$col = $data['col'] ?? null;

if (!is_int($row) || !is_int($col)) {
    fail('بيانات غير صحيحة');
}

if (!isset($_SESSION['game'], $_SESSION['board'], $_SESSION['turn'])) {
    fail('اللعبة غير موجودة، ابدأ من الصفحة الرئيسية');
}

$game   = $_SESSION['game'];
$board  = $_SESSION['board'];
$symbol = $_SESSION['turn'];
$chaos  = !empty($_SESSION['chaos']);

if ($board->isLocked($row, $col)) {
    fail('🔒 هذه الخانة مقفلة!');
}

if (!$board->makeMove($row, $col, $symbol)) {
    fail('هذه الخانة غير متاحة');
}

// سجل الجولة (للإعادة)
$log = $_SESSION['log'] ?? [];
$log[] = ['t' => 'move', 'r' => $row, 'c' => $col, 's' => $symbol];

$_SESSION['moves'] = ($_SESSION['moves'] ?? 0) + 1;
$board->tickLocks();

$result = $board->checkWinner(); // X, O, Draw, Continue
$winLine = $board->getWinLine(); // لازم قبل reset
$winnerName = null;

if ($result === 'X') {
    $game->addWin(1);
    $winnerName = $game->getPlayer1();
} elseif ($result === 'O') {
    $game->addWin(2);
    $winnerName = $game->getPlayer2();
} elseif ($result === 'Draw') {
    $game->addDraw();
}

// نحفظ نتيجة الجولة مع سجل الحركات إذا خلصت
if ($result !== 'Continue') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO results (player1, player2, result, winner_name, moves_json)
             VALUES (:p1, :p2, :result, :winner, :moves)'
        );
        $stmt->execute([
            ':p1'     => $game->getPlayer1(),
            ':p2'     => $game->getPlayer2(),
            ':result' => $result,
            ':winner' => $winnerName,
            ':moves'  => json_encode(['events' => $log, 'winLine' => $winLine]),
        ]);
    } catch (PDOException $e) {
        error_log('DB error: ' . $e->getMessage());
    }
}

$event = null;

if ($result === 'Continue') {
    $_SESSION['turn'] = ($symbol === 'X') ? 'O' : 'X';

    // وضع الفوضى: حدث كل 3 حركات
    if ($chaos && $_SESSION['moves'] % 3 === 0) {
        $choices = ['vanish', 'double'];
        if ($board->countEmpty() >= 4) {
            $choices[] = 'lock';
        }
        $pick = $choices[array_rand($choices)];

        if ($pick === 'lock') {
            $cell = $board->lockRandomEmpty(2);
            if ($cell) {
                $event = ['type' => 'lock', 'cell' => $cell,
                          'message' => '🔒 تم قفل خانة لحركتين!'];
                $log[] = ['t' => 'lock', 'r' => $cell[0], 'c' => $cell[1]];
            }
        } elseif ($pick === 'vanish') {
            $cell = $board->vanishRandom();
            if ($cell) {
                $event = ['type' => 'vanish', 'cell' => $cell,
                          'message' => '💨 اختفى رمز من اللوح!'];
                $log[] = ['t' => 'vanish', 'r' => $cell[0], 'c' => $cell[1]];
            }
        } else {
            $_SESSION['turn'] = $symbol; // نفس اللاعب يلعب مرة ثانية
            $who = ($symbol === 'X') ? $game->getPlayer1() : $game->getPlayer2();
            $event = ['type' => 'double',
                      'message' => '⚡ دور مزدوج لـ ' . $who . '!'];
            $log[] = ['t' => 'double', 's' => $symbol];
        }
    }
    $_SESSION['log'] = $log;
} else {
    // الجولة خلصت: ننظف اللوح والسجل ونرجع الدور لـ X
    $board->reset();
    $_SESSION['turn'] = 'X';
    $_SESSION['moves'] = 0;
    $_SESSION['log'] = [];
}

$nextTurn = $_SESSION['turn'];

echo json_encode([
    'success'    => true,
    'symbol'     => $symbol,
    'row'        => $row,
    'col'        => $col,
    'result'     => $result,
    'winnerName' => $winnerName,
    'winLine'    => $winLine,
    'event'      => $event,
    'locked'     => $board->getLockedCells(),
    'nextTurn'   => $nextTurn,
    'nextName'   => ($nextTurn === 'X') ? $game->getPlayer1() : $game->getPlayer2(),
    'scores'     => [
        'player1' => $game->getPlayer1(),
        'player2' => $game->getPlayer2(),
        'score1'  => $game->getScore1(),
        'score2'  => $game->getScore2(),
        'draws'   => $game->getDraws(),
    ],
]);