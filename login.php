<?php include __DIR__ . '/partials/header.php'; ?>
<?php
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
        set_flash('欢迎回来，' . $user['username'] . '！');
        header('Location: /index.php');
        exit;
    } else {
        $error = '用户名或密码错误。';
    }
}
?>

<div class="grid">
    <div class="card">
        <h3>登录</h3>
        <?php if ($error): ?>
            <div class="banner loud">❗ <?php echo escape_html($error); ?></div>
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
            <button class="btn" type="submit">登录</button>
        </form>
        <p class="muted" style="margin-top:14px;">默认管理员：<strong>admin / admin123</strong></p>
    </div>
    <div class="card">
        <h3>还没有账号？</h3>
        <p class="muted">注册后即可借阅图书、查看借阅状态。</p>
        <a class="btn secondary" href="/register.php">前往注册</a>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
