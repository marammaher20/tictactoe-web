<?php
require_once 'config/db.php';
session_start();

$avatars = ['🦊', '🐼', '🐯', '🦁', '🐸', '🐙', '🤖', '👽'];

// رمز حماية من هجمات CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name   = mb_substr(trim($_POST['name'] ?? ''), 0, 20);
    $avatar = $_POST['avatar'] ?? $avatars[0];
    if (!in_array($avatar, $avatars, true)) {
        $avatar = $avatars[0];
    }

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'طلب غير صالح، حدّثي الصفحة وحاولي مرة ثانية';
    } elseif ($name === '') {
        $error = 'اكتبي اسمك أول!';
    } else {
        try {
            $pdo = getConnection();

            if ($action === 'create') {
                // حروف وأرقام بدون التباس (بدون O و0 و I و1 و L)
                $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

                for ($try = 0; $try < 5; $try++) {
                    $code = '';
                    for ($i = 0; $i < 5; $i++) {
                        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                    }
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO rooms (code, player_x, avatar_x)
                             VALUES (:c, :n, :a)'
                        );
                        $stmt->execute([':c' => $code, ':n' => $name, ':a' => $avatar]);

                        $_SESSION['online'][$code] = 'X';
                        header('Location: room.php?code=' . $code);
                        exit;
                    } catch (PDOException $e) {
                        // 23000 = الكود مكرر، نجرّب كود ثاني
                        if ($e->getCode() !== '23000') {
                            throw $e;
                        }
                    }
                }
                $error = 'تعذّر إنشاء الغرفة، حاولي مرة ثانية';

            } elseif ($action === 'join') {
                $code = strtoupper(trim($_POST['code'] ?? ''));

                if (!preg_match('/^[A-Z2-9]{5}$/', $code)) {
                    $error = 'كود الغرفة غير صحيح';
                } elseif (isset($_SESSION['online'][$code])) {
                    // إنتِ أصلاً داخل هالغرفة
                    header('Location: room.php?code=' . $code);
                    exit;
                } else {
                    // تحديث واحد بيضمن إنه بس لاعبة وحدة تدخل
                    $stmt = $pdo->prepare(
                        "UPDATE rooms
                         SET player_o = :n, avatar_o = :a, status = 'playing', version = version + 1
                         WHERE code = :c AND player_o IS NULL"
                    );
                    $stmt->execute([':n' => $name, ':a' => $avatar, ':c' => $code]);

                    if ($stmt->rowCount() === 1) {
                        $_SESSION['online'][$code] = 'O';
                        header('Location: room.php?code=' . $code);
                        exit;
                    }

                    $check = $pdo->prepare('SELECT id FROM rooms WHERE code = :c');
                    $check->execute([':c' => $code]);
                    $error = $check->fetch()
                        ? 'هالغرفة ممتلئة'
                        : 'ما في غرفة بهالكود';
                }
            }
        } catch (PDOException $e) {
            error_log('Online error: ' . $e->getMessage());
            $error = 'حصل خطأ بقاعدة البيانات';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لعب أونلاين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="css/style.css?v=7" rel="stylesheet">
    <script src="js/theme.js?v=7"></script>
</head>
<body class="neon-body">

<ul class="bubbles">
    <li></li><li></li><li></li><li></li><li></li><li></li><li></li>
</ul>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="neon-card p-4" style="max-width: 450px; width: 100%;">

        <h1 class="neon-title text-center fs-2 mb-4">🌐 لعب أونلاين</h1>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

            <div class="mb-3">
                <label for="name" class="form-label">اسمك</label>
                <input type="text" class="form-control" id="name" name="name" maxlength="20"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                <div class="avatar-picker mt-2">
                    <?php $sel = $_POST['avatar'] ?? $avatars[0]; ?>
                    <?php foreach ($avatars as $i => $a): ?>
                        <input type="radio" name="avatar" id="av_<?= $i ?>" value="<?= $a ?>"
                               <?= $a === $sel ? 'checked' : '' ?>>
                        <label for="av_<?= $i ?>"><?= $a ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <p class="neon-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button type="submit" name="action" value="create" class="btn neon-btn w-100 mb-4">
                ➕ إنشاء غرفة جديدة
            </button>

            <hr class="opacity-25">

            <div class="mb-3 mt-3">
                <label for="code" class="form-label">عندك كود غرفة؟</label>
                <input type="text" class="form-control text-center" id="code" name="code"
                       maxlength="5" placeholder="مثال: K7P3X" style="letter-spacing: 6px; text-transform: uppercase;"
                       value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">
            </div>

            <button type="submit" name="action" value="join" class="btn neon-btn w-100">
                🚪 انضمام للغرفة
            </button>
        </form>

        <a href="index.php" class="d-block text-center mt-3 text-info">🏠 رجوع للرئيسية</a>

    </div>
</div>

</body>
</html>