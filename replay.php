<?php
require_once 'config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: leaderboard.php');
    exit;
}

$row = null;
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare(
        'SELECT player1, player2, result, winner_name, moves_json
         FROM results WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$replay = $row && !empty($row['moves_json']) ? json_decode($row['moves_json'], true) : null;

if (!is_array($replay) || !isset($replay['events'])) {
    header('Location: leaderboard.php');
    exit;
}

// نمرّر البيانات للـ JavaScript بشكل آمن
$payload = json_encode([
    'p1'      => $row['player1'],
    'p2'      => $row['player2'],
    'result'  => $row['result'],
    'winner'  => $row['winner_name'],
    'events'  => $replay['events'],
    'winLine' => $replay['winLine'] ?? [],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إعادة الجولة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=7" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container py-4 text-center" style="max-width: 420px;">

    <h1 class="neon-title fs-3 mb-3">🎬 إعادة الجولة</h1>

    <div class="neon-card p-3 mb-3">
        <div class="fw-bold">
            <?= htmlspecialchars($row['player1']) ?> (X) ⚔️ <?= htmlspecialchars($row['player2']) ?> (O)
        </div>
        <div id="info" class="text-muted mt-1">جاهزة للتشغيل</div>
    </div>

    <div class="board-stage">
        <div id="board">
            <?php for ($r = 0; $r < 3; $r++): ?>
                <?php for ($c = 0; $c < 3; $c++): ?>
                    <button class="cell btn" data-row="<?= $r ?>" data-col="<?= $c ?>" disabled></button>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button id="playBtn" class="btn neon-btn flex-fill">▶ تشغيل</button>
        <button id="restartBtn" class="btn neon-btn flex-fill">🔁 من البداية</button>
        <button id="speedBtn" class="btn neon-btn flex-fill">⏩ ×1</button>
    </div>

    <a href="leaderboard.php" class="d-block text-center mt-3 text-info">🏆 رجوع للوحة الصدارة</a>
</div>

<div id="toast" class="chaos-toast"></div>

<script type="application/json" id="replayData"><?= $payload ?></script>
<script src="js/replay.js?v=7"></script>
<script src="js/tilt.js?v=7"></script>
</body>
</html>