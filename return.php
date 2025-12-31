<?php
include __DIR__ . '/init.php';
require_login();

$borrowingId = (int)($_POST['borrowing_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM borrowings WHERE id = :id AND (user_id = :user_id OR :is_admin = 1)');
$stmt->execute([
    ':id' => $borrowingId,
    ':user_id' => current_user()['id'],
    ':is_admin' => is_admin() ? 1 : 0,
]);

$borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$borrowing) {
    set_flash('未找到借阅记录。');
    header('Location: /index.php');
    exit;
}

if (!empty($borrowing['returned_at'])) {
    set_flash('该书已归还。');
    header('Location: /index.php');
    exit;
}

$pdo->beginTransaction();
$pdo->prepare('UPDATE borrowings SET returned_at = :returned WHERE id = :id')
    ->execute([':returned' => date('Y-m-d'), ':id' => $borrowingId]);
$pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = :book_id')
    ->execute([':book_id' => $borrowing['book_id']]);
$pdo->commit();

set_flash('归还成功，感谢按时归还。');
header('Location: /index.php');
exit;
