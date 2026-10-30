<?php
require_once 'classes/Board.php';
require_once 'classes/Game.php';
session_start();
require_once 'config/csrf.php';
// إذا ما في أسماء، رجّعه لصفحة البداية
if (!isset($_SESSION['player1'], $_SESSION['player2'])) {
    header('Location: index.php');
    exit;
}

// أنشئ لعبة جديدة إذا ما في أو إذا تغيّرت الأسماء
if (!isset($_SESSION['game'])
    || $_SESSION['game']->getPlayer1() !== $_SESSION['player1']
    || $_SESSION['game']->getPlayer2() !== $_SESSION['player2']) {
    $_SESSION['game'] = new Game($_SESSION['player1'], $_SESSION['player2']);
    $_SESSION['board'] = new Board();
    $_SESSION['turn'] = 'X';
}

$game = $_SESSION['game'];
$cells = $_SESSION['board']->getCells();
$turn  = $_SESSION['turn'];
$turnName = ($turn === 'X') ? $game->getPlayer1() : $game->getPlayer2();
$av1 = $_SESSION['avatar1'] ?? '🦊';
$av2 = $_SESSION['avatar2'] ?? '🐼';
$chaosOn = !empty($_SESSION['chaos']);
$lockedSet = [];
foreach ($_SESSION['board']->getLockedCells() as [$lr, $lc]) {
    $lockedSet[$lr . '-' . $lc] = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Tic-Tac-Toe - اللعبة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=7" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container py-4 text-center" style="max-width: 420px;">

    <!-- كروت اللاعبين -->
    <div class="d-flex gap-3 mb-3">
        <div class="player-card <?= $turn === 'X' ? 'active' : '' ?>" data-symbol="X">
            <div class="avatar"><?= $av1 ?></div>
            <div class="pname"><?= htmlspecialchars($game->getPlayer1()) ?></div>
            <div class="psym x">X</div>
        </div>
        <div class="player-card <?= $turn === 'O' ? 'active' : '' ?>" data-symbol="O">
            <div class="avatar"><?= $av2 ?></div>
            <div class="pname"><?= htmlspecialchars($game->getPlayer2()) ?></div>
            <div class="psym o">O</div>
        </div>
    </div>

    <!-- القسم العلوي: معلومات اللعبة -->
    <div class="neon-card p-3 mb-3">
        <div id="turnLabel" class="fw-bold fs-5">
            دور <?= htmlspecialchars($turnName) ?> — <?= $turn ?>
        </div>
        <div id="scoreLabel" class="text-muted mt-1">
            <?= htmlspecialchars($game->getPlayer1()) ?>: <?= $game->getScore1() ?> نقاط |
            <?= htmlspecialchars($game->getPlayer2()) ?>: <?= $game->getScore2() ?> نقاط |
            تعادل: <?= $game->getDraws() ?>
        </div>
        <?php if ($chaosOn): ?>
            <div class="chaos-badge">🌀 وضع الفوضى شغّال</div>
        <?php endif; ?>
    </div>

    <!-- القسم الأوسط: لوح 3×3 -->
    <div class="board-stage">
        <div id="board">
            <?php for ($r = 0; $r < 3; $r++): ?>
                <?php for ($c = 0; $c < 3; $c++): ?>
                    <?php $v = $cells[$r][$c]; $isLocked = isset($lockedSet[$r . '-' . $c]); ?>
                    <button class="cell btn <?= strtolower($v) ?> <?= $isLocked ? 'locked' : '' ?>"
                            data-row="<?= $r ?>" data-col="<?= $c ?>"
                            <?= ($v !== '' || $isLocked) ? 'disabled' : '' ?>><?= $isLocked ? '🔒' : $v ?></button>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <a href="index.php" class="btn neon-btn flex-fill">🏠 الرئيسية</a>
        <button id="resetBtn" class="btn neon-btn flex-fill">♻️ تصفير النقاط</button>
    </div>
</div>

<!-- إشعار أحداث الفوضى -->
<div id="chaosToast" class="chaos-toast"></div>

<!-- نافذة النتيجة -->
<div id="resultOverlay" class="result-overlay">
    <div class="result-box neon-card p-4 text-center">
        <div id="resultEmoji" class="result-emoji"></div>
        <h2 id="resultText" class="neon-title fs-3"></h2>
        <button id="nextRoundBtn" class="btn neon-btn mt-3 px-4">جولة جديدة 🔁</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="js/game.js?v=10"></script>
<script src="js/tilt.js?v=10"></script>
</body>
</html>