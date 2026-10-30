<?php
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$code = is_string($_GET['code'] ?? null) ? strtoupper($_GET['code']) : '';

if (!preg_match('/^[A-Z2-9]{5}$/', $code) || !isset($_SESSION['online'][$code])) {
    header('Location: online.php');
    exit;
}

$cfg = json_encode(
    ['code' => $code, 'csrf' => $_SESSION['csrf']],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الغرفة <?= htmlspecialchars($code) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=8" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container py-4 text-center" style="max-width: 420px;">

    <!-- شاشة الانتظار -->
    <div id="waitingBox" class="neon-card p-4">
        <h2 class="neon-title fs-4">🌐 بانتظار لاعبة ثانية</h2>
        <p class="mb-1">شاركي هالكود معها:</p>
        <div class="room-code my-3"><?= htmlspecialchars($code) ?></div>
        <button id="copyBtn" type="button" class="btn neon-btn px-4">📋 نسخ الكود</button>
        <p class="text-muted mt-3 mb-0">⏳ الصفحة بتتحدث لحالها لما تنضم...</p>
    </div>

    <!-- شاشة اللعب -->
    <div id="gameBox" class="d-none">

        <div class="d-flex gap-3 mb-3">
            <div id="cardX" class="player-card" data-symbol="X">
                <div class="avatar"></div>
                <div class="pname"></div>
                <div class="psym x">X</div>
            </div>
            <div id="cardO" class="player-card" data-symbol="O">
                <div class="avatar"></div>
                <div class="pname"></div>
                <div class="psym o">O</div>
            </div>
        </div>

        <div class="neon-card p-3 mb-3">
            <div id="turnLabel" class="fw-bold fs-5"></div>
            <div id="scoreLabel" class="text-muted mt-1"></div>
        </div>

        <div class="board-stage">
            <div id="board">
                <?php for ($i = 0; $i < 9; $i++): ?>
                    <button class="cell btn" data-i="<?= $i ?>" disabled></button>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <span class="text-muted">الغرفة: <?= htmlspecialchars($code) ?></span>
        <a href="online.php" class="btn neon-btn w-100 mt-2">🚪 خروج</a>
    </div>
</div>

<div id="toast" class="chaos-toast"></div>

<div id="resultOverlay" class="result-overlay">
    <div class="result-box neon-card p-4 text-center">
        <div id="resultEmoji" class="result-emoji"></div>
        <h2 id="resultText" class="neon-title fs-3"></h2>
        <button id="nextRoundBtn" class="btn neon-btn mt-3 px-4">جولة جديدة 🔁</button>
    </div>
</div>

<script type="application/json" id="roomCfg"><?= $cfg ?></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="js/room.js?v=8"></script>
<script src="js/tilt.js?v=7"></script>
</body>
</html>