<?php include __DIR__ . '/partials/header.php'; ?>
<?php require_login('admin'); ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $total = max(1, (int)($_POST['total_copies'] ?? 1));

        if ($title === '' || $author === '') {
            set_flash('请填写完整的书名和作者。');
        } else {
            $stmt = $pdo->prepare('INSERT INTO books (title, author, description, total_copies, available_copies) VALUES (:title, :author, :description, :total, :available)');
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':description' => $description,
                ':total' => $total,
                ':available' => $total,
            ]);
            set_flash('新增图书成功。');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $total = max(1, (int)($_POST['total_copies'] ?? 1));

        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book) {
            $borrowed = $book['total_copies'] - $book['available_copies'];
            $available = max(0, $total - $borrowed);
            $pdo->prepare('UPDATE books SET title = :title, author = :author, description = :description, total_copies = :total, available_copies = :available WHERE id = :id')
                ->execute([
                    ':title' => $title,
                    ':author' => $author,
                    ':description' => $description,
                    ':total' => $total,
                    ':available' => $available,
                    ':id' => $id,
                ]);
            set_flash('已更新图书信息。');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM borrowings WHERE book_id = :id AND returned_at IS NULL');
        $countStmt->execute([':id' => $id]);
        $activeBorrow = (int)$countStmt->fetchColumn();

        if ($activeBorrow > 0) {
            set_flash('有未归还的借阅，无法删除。');
        } else {
            $pdo->prepare('DELETE FROM books WHERE id = :id')->execute([':id' => $id]);
            set_flash('图书已删除。');
        }
    }

    header('Location: /admin.php');
    exit;
}

$books = $pdo->query('SELECT * FROM books ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$borrowingStmt = $pdo->query('SELECT br.id, br.borrow_date, br.due_date, br.returned_at, u.username, b.title FROM borrowings br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    ORDER BY br.borrow_date DESC');
$borrows = $borrowingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $flash = flash_message(); if ($flash): ?>
    <div class="banner info">ℹ️ <?php echo escape_html($flash); ?></div>
<?php endif; ?>

<div class="card">
    <h3>新增图书</h3>
    <form method="POST" class="form-inline">
        <input type="hidden" name="action" value="add">
        <input type="text" name="title" placeholder="书名" required>
        <input type="text" name="author" placeholder="作者" required>
        <input type="number" name="total_copies" placeholder="总册数" min="1" value="1" required>
        <input type="text" name="description" placeholder="一句话简介">
        <button class="btn" type="submit">添加</button>
    </form>
</div>

<div class="card" style="margin-top:18px;">
    <h3>馆藏管理</h3>
    <table class="table">
        <thead>
            <tr>
                <th>书名</th>
                <th>作者</th>
                <th>总册数</th>
                <th>可借</th>
                <th>简介</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <?php $formId = 'update-' . $book['id']; ?>
                <tr>
                    <td><input form="<?php echo $formId; ?>" type="text" name="title" value="<?php echo escape_html($book['title']); ?>" required></td>
                    <td><input form="<?php echo $formId; ?>" type="text" name="author" value="<?php echo escape_html($book['author']); ?>" required></td>
                    <td><input form="<?php echo $formId; ?>" type="number" name="total_copies" min="1" value="<?php echo $book['total_copies']; ?>" required></td>
                    <td><span class="badge info"><?php echo $book['available_copies']; ?></span></td>
                    <td><input form="<?php echo $formId; ?>" type="text" name="description" value="<?php echo escape_html($book['description']); ?>"></td>
                    <td style="display:flex;gap:8px;">
                        <button class="btn secondary" type="submit" form="<?php echo $formId; ?>">保存</button>
                        <form method="POST" onsubmit="return confirm('确认删除该图书？');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                            <button class="btn danger" type="submit">删除</button>
                        </form>
                    </td>
                </tr>
                <form id="<?php echo $formId; ?>" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                </form>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:18px;">
    <h3>借阅记录</h3>
    <table class="table">
        <thead>
            <tr>
                <th>读者</th>
                <th>书名</th>
                <th>借出日</th>
                <th>到期日</th>
                <th>状态</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrows as $row): ?>
                <?php
                $returned = !empty($row['returned_at']);
                $daysLeft = $returned ? null : floor((strtotime($row['due_date']) - time()) / 86400);
                ?>
                <tr>
                    <td><?php echo escape_html($row['username']); ?></td>
                    <td><?php echo escape_html($row['title']); ?></td>
                    <td><?php echo format_date($row['borrow_date']); ?></td>
                    <td><?php echo format_date($row['due_date']); ?></td>
                    <td>
                        <?php if ($returned): ?>
                            <span class="badge success">已归还</span>
                        <?php elseif ($daysLeft !== null && $daysLeft < 0): ?>
                            <span class="badge danger">逾期 <?php echo abs($daysLeft); ?> 天</span>
                        <?php elseif ($daysLeft !== null && $daysLeft <= 5): ?>
                            <span class="badge warn">还剩 <?php echo $daysLeft; ?> 天</span>
                        <?php else: ?>
                            <span class="badge info">借阅中</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
