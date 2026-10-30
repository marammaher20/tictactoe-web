<?php
require_once '../classes/Board.php';
require_once '../classes/Game.php';
require_once '../config/csrf.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح (CSRF)']);
    exit;
}

if (!isset($_SESSION['game'], $_SESSION['board'])) {
    echo json_encode(['success' => false, 'message' => 'اللعبة غير موجودة']);
    exit;
}

$game = $_SESSION['game'];
$game->resetScores();
$_SESSION['board']->reset();
$_SESSION['turn'] = 'X';
$_SESSION['moves'] = 0;
$_SESSION['log'] = [];

echo json_encode([
    'success'  => true,
    'nextName' => $game->getPlayer1(),
    'scores'   => [
        'player1' => $game->getPlayer1(),
        'player2' => $game->getPlayer2(),
        'score1'  => $game->getScore1(),
        'score2'  => $game->getScore2(),
        'draws'   => $game->getDraws(),
    ],
]);