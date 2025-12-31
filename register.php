<?php include __DIR__ . '/partials/header.php'; ?>
<?php
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password === '' || $confirm === '') {
        $error = '请完整填写信息。';
    } elseif ($password !== $confirm) {
        $error = '两次输入的密码不一致。';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $error = '用户名已存在，请更换。';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password, "user")')
                ->execute([':username' => $username, ':password' => $hash]);
            $success = '注册成功，请登录';
        }
    }
}
?>

<div class="grid">
    <div class="card">
        <h3>注册</h3>
        <?php if ($error): ?>
            <div class="banner loud">⚠️ <?php echo escape_html($error); ?></div>
        <?php elseif ($success): ?>
            <div class="banner info">✅ <?php echo escape_html($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm">确认密码</label>
                <input type="password" id="confirm" name="confirm" required>
            </div>
            <button class="btn" type="submit">创建账号</button>
        </form>
    </div>
    <div class="card">
        <h3>已经有账号？</h3>
        <p class="muted">立即登录，继续阅读旅程。</p>
        <a class="btn secondary" href="/login.php">去登录</a>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
