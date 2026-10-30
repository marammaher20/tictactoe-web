<?php
require_once 'config/db.php';

$topPlayers = [];
$recent = [];
$error = '';

try {
    $pdo = getConnection();

    // ترتيب اللاعبين حسب عدد الانتصارات
    $topPlayers = $pdo->query(
        "SELECT winner_name, COUNT(*) AS wins
         FROM results
         WHERE result IN ('X', 'O') AND winner_name IS NOT NULL
         GROUP BY winner_name
         ORDER BY wins DESC
         LIMIT 10"
    )->fetchAll();

    // آخر 10 جولات
        $recent = $pdo->query(
        "SELECT id, player1, player2, result, winner_name, played_at,
                (moves_json IS NOT NULL) AS has_replay
         FROM results
         ORDER BY id DESC
         LIMIT 10"
    )->fetchAll();
} catch (PDOException $e) {
    $error = 'تعذّر قراءة البيانات من قاعدة البيانات';
    error_log($e->getMessage());
}

$medals = ['🥇', '🥈', '🥉'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة الصدارة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=7" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container py-4" style="max-width: 600px;">

    <h1 class="neon-title text-center mb-4">🏆 لوحة الصدارة</h1>

    <?php if ($error !== ''): ?>
        <p class="neon-error text-center"><?= $error ?></p>
    <?php endif; ?>

    <div class="neon-card p-3 mb-4">
        <h5 class="mb-3">أكثر اللاعبين فوزاً</h5>
        <?php if (empty($topPlayers)): ?>
            <p class="text-muted mb-0">لا توجد نتائج بعد، العبوا جولة أولاً!</p>
        <?php else: ?>
            <table class="table table-borderless text-white mb-0">
                <?php foreach ($topPlayers as $i => $p): ?>
                    <tr>
                        <td style="width: 50px;"><?= $medals[$i] ?? ($i + 1) ?></td>
                        <td><?= htmlspecialchars($p['winner_name']) ?></td>
                        <td class="text-end"><?= (int)$p['wins'] ?> فوز</td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="neon-card p-3 mb-4">
        <h5 class="mb-3">آخر الجولات</h5>
        <?php if (empty($recent)): ?>
            <p class="text-muted mb-0">لا توجد جولات بعد.</p>
        <?php else: ?>
            <table class="table table-borderless text-white mb-0">
                            <table class="table table-borderless text-white mb-0 align-middle">
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($r['player1']) ?> ⚔️ <?= htmlspecialchars($r['player2']) ?>
                        </td>
                        <td class="text-end">
                            <?= $r['result'] === 'Draw'
                                ? '🤝 تعادل'
                                : '🏆 ' . htmlspecialchars($r['winner_name']) ?>
                        </td>
                        <td style="width: 60px;">
                            <?php if ($r['has_replay']): ?>
                                <a href="replay.php?id=<?= (int)$r['id'] ?>"
                                   class="btn btn-sm neon-btn" title="إعادة العرض">▶</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <a href="index.php" class="btn neon-btn px-4">🎮 رجوع للعبة</a>
    </div>

</div>
</body>
</html>