<?php
session_start();
require_once 'config/csrf.php';

$avatars = ['🦊', '🐼', '🐯', '🦁', '🐸', '🐙', '🤖', '👽'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = is_string($_POST['player1'] ?? null) ? mb_substr(trim($_POST['player1']), 0, 20) : '';
    $p2 = is_string($_POST['player2'] ?? null) ? mb_substr(trim($_POST['player2']), 0, 20) : '';
    $a1 = $_POST['avatar1'] ?? $avatars[0];
    $a2 = $_POST['avatar2'] ?? $avatars[1];

    // نتأكد إنه الأفاتار من القائمة المسموحة فقط
    if (!in_array($a1, $avatars, true)) $a1 = $avatars[0];
    if (!in_array($a2, $avatars, true)) $a2 = $avatars[1];

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'طلب غير صالح، حدّثي الصفحة وحاولي مرة ثانية';
    } elseif ($p1 === '' || $p2 === '') {
        $error = 'يجب ملء حقول الأسماء!';
    } else {
        session_regenerate_id(true); // حماية من سرقة الجلسة
        $_SESSION['player1'] = $p1;
        $_SESSION['player2'] = $p2;
        $_SESSION['avatar1'] = $a1;
        $_SESSION['avatar2'] = $a2;
        $_SESSION['chaos'] = isset($_POST['chaos']);
        $_SESSION['moves'] = 0;
        $_SESSION['log'] = [];
        unset($_SESSION['game'], $_SESSION['board'], $_SESSION['turn']);
        header('Location: game.php');
        exit;
    }
}

$sel1 = $_POST['avatar1'] ?? $avatars[0];
$sel2 = $_POST['avatar2'] ?? $avatars[1];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tic-Tac-Toe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=10" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="neon-card p-4" style="max-width: 450px; width: 100%;">

        <h1 class="neon-title text-center mb-4">🎮 Tic-Tac-Toe 🎮</h1>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div class="mb-3">
                <label for="player1" class="form-label">اسم اللاعب الأول (X)</label>
                <input type="text" class="form-control" id="player1" name="player1" maxlength="20"
                       value="<?= htmlspecialchars($_POST['player1'] ?? '') ?>">
                <div class="avatar-picker mt-2">
                    <?php foreach ($avatars as $i => $a): ?>
                        <input type="radio" name="avatar1" id="a1_<?= $i ?>" value="<?= $a ?>"
                               <?= $a === $sel1 ? 'checked' : '' ?>>
                        <label for="a1_<?= $i ?>"><?= $a ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="player2" class="form-label">اسم اللاعب الثاني (O)</label>
                <input type="text" class="form-control" id="player2" name="player2" maxlength="20"
                       value="<?= htmlspecialchars($_POST['player2'] ?? '') ?>">
                <div class="avatar-picker mt-2">
                    <?php foreach ($avatars as $i => $a): ?>
                        <input type="radio" name="avatar2" id="a2_<?= $i ?>" value="<?= $a ?>"
                               <?= $a === $sel2 ? 'checked' : '' ?>>
                        <label for="a2_<?= $i ?>"><?= $a ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <p class="neon-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <!-- مفتاح وضع الفوضى -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="chaos" name="chaos" value="1"
                       <?= isset($_POST['chaos']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="chaos">🌀 وضع الفوضى (أحداث مفاجئة!)</label>
            </div>

            <button type="submit" class="btn neon-btn w-100">ابدأ اللعبة 🚀</button>
        </form>

        <a href="leaderboard.php" class="d-block text-center mt-3 text-info">🏆 لوحة الصدارة</a>
        <a href="online.php" class="d-block text-center mt-2 text-info">🌐 العب أونلاين</a>
    </div>
</div>

</body>
</html>