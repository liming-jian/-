<?php include __DIR__ . '/partials/header.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();

    $bookId = (int)($_POST['book_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
    $stmt->execute([':id' => $bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        set_flash('未找到图书。');
    } elseif ((int)$book['available_copies'] < 1) {
        set_flash('这本书暂时被借完啦。');
    } else {
        $pdo->beginTransaction();
        $insert = $pdo->prepare('INSERT INTO borrowings (user_id, book_id, borrow_date, due_date) VALUES (:user_id, :book_id, :borrow_date, :due_date)');
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+30 days'));
        $insert->execute([
            ':user_id' => current_user()['id'],
            ':book_id' => $bookId,
            ':borrow_date' => $borrowDate,
            ':due_date' => $dueDate,
        ]);

        $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = :id')
            ->execute([':id' => $bookId]);

        $pdo->commit();
        set_flash('已成功借阅《' . $book['title'] . '》，默认借阅期 30 天。');
    }

    header('Location: /books.php');
    exit;
}

$stmt = $pdo->query('SELECT * FROM books ORDER BY created_at DESC');
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $flash = flash_message(); if ($flash): ?>
    <div class="banner info">🔔 <?php echo escape_html($flash); ?></div>
<?php endif; ?>

<div class="grid">
    <?php foreach ($books as $book): ?>
        <div class="card">
            <h3><?php echo escape_html($book['title']); ?></h3>
            <p class="muted">作者：<?php echo escape_html($book['author']); ?></p>
            <p><?php echo escape_html($book['description'] ?? ''); ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
                <span class="badge <?php echo ($book['available_copies'] > 0) ? 'info' : 'danger'; ?>">
                    <?php echo $book['available_copies'] > 0 ? '可借 ' . $book['available_copies'] . ' 本' : '已借完'; ?>
                </span>
                <?php if (is_logged_in()): ?>
                    <form method="POST">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button class="btn<?php echo ($book['available_copies'] > 0) ? '' : ' secondary'; ?>" type="submit" <?php echo ($book['available_copies'] > 0) ? '' : 'disabled'; ?>>
                            借阅 30 天
                        </button>
                    </form>
                <?php else: ?>
                    <a class="btn secondary" href="/login.php">登录后借阅</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
