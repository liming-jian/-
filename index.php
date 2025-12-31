<?php include __DIR__ . '/partials/header.php'; ?>
<section class="hero">
    <h1>欢迎来到阅书·云廊</h1>
    <p>为读者与管理员打造的轻盈图书管理系统。柔和质感的界面、流畅的操作体验，让借阅和维护同样舒心。</p>
    <div class="list-inline" style="margin-top:14px;">
        <li>📚 用户注册与登录</li>
        <li>🕒 借阅期默认 30 天</li>
        <li>📣 到期喇叭提醒</li>
        <li>🛠️ 管理员增删改查</li>
    </div>
</section>

<?php $flash = flash_message(); if ($flash): ?>
    <div class="banner info">🔔 <?php echo escape_html($flash); ?></div>
<?php endif; ?>

<?php if (is_logged_in()): ?>
    <?php
    $stmt = $pdo->prepare('SELECT b.title, b.author, br.borrow_date, br.due_date, br.returned_at, br.id
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        WHERE br.user_id = :user_id
        ORDER BY br.borrow_date DESC');
    $stmt->execute([':user_id' => current_user()['id']]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $urgent = array_filter($borrowings, function (array $row) {
        if ($row['returned_at']) {
            return false;
        }
        $daysLeft = (strtotime($row['due_date']) - time()) / 86400;
        return $daysLeft <= 5;
    });
    ?>

    <?php if (count($urgent) > 0): ?>
        <div class="banner loud">📣 您有即将到期的借阅，请及时归还或续借。</div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h3>我的借阅</h3>
            <?php if (count($borrowings) === 0): ?>
                <p class="empty">还没有借阅记录，去图书馆逛逛吧。</p>
            <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>书名</th>
                        <th>作者</th>
                        <th>借出日</th>
                        <th>到期日</th>
                        <th>状态</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowings as $row): ?>
                        <?php
                        $returned = !empty($row['returned_at']);
                        $daysLeft = ($returned) ? null : floor((strtotime($row['due_date']) - time()) / 86400);
                        ?>
                        <tr>
                            <td><?php echo escape_html($row['title']); ?></td>
                            <td class="muted"><?php echo escape_html($row['author']); ?></td>
                            <td><?php echo format_date($row['borrow_date']); ?></td>
                            <td><?php echo format_date($row['due_date']); ?></td>
                            <td>
                                <?php if ($returned): ?>
                                    <span class="badge success">已归还</span>
                                <?php elseif ($daysLeft !== null && $daysLeft < 0): ?>
                                    <span class="badge danger">已逾期 <?php echo abs($daysLeft); ?> 天</span>
                                <?php elseif ($daysLeft !== null && $daysLeft <= 5): ?>
                                    <span class="badge warn">还剩 <?php echo $daysLeft; ?> 天</span>
                                <?php else: ?>
                                    <span class="badge info">借阅中</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$returned): ?>
                                    <form action="/return.php" method="POST">
                                        <input type="hidden" name="borrowing_id" value="<?php echo $row['id']; ?>">
                                        <button class="btn secondary" type="submit">归还</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>快速入口</h3>
            <p class="muted">随时查看热门书籍，或切换到管理视角维护馆藏。</p>
            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
                <a class="btn" href="/books.php">查看图书</a>
                <?php if (is_admin()): ?>
                    <a class="btn secondary" href="/admin.php">管理后台</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid">
        <div class="card">
            <h3>注册成为读者</h3>
            <p class="muted">建立你的专属书架，收藏每一次借阅足迹。</p>
            <a class="btn" href="/register.php">开始注册</a>
        </div>
        <div class="card">
            <h3>已有账号？</h3>
            <p class="muted">登录后即可浏览、借阅或进入后台。</p>
            <a class="btn secondary" href="/login.php">去登录</a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
