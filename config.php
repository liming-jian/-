<?php

const DB_PATH = __DIR__ . '/data/library.sqlite';

function ensure_data_directory(): void
{
    $directory = dirname(DB_PATH);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    ensure_data_directory();
    $newDatabase = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initialize_schema($pdo);

    if ($newDatabase) {
        seed_defaults($pdo);
    }

    return $pdo;
}

function initialize_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "user"
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            author TEXT NOT NULL,
            description TEXT,
            total_copies INTEGER NOT NULL DEFAULT 1,
            available_copies INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS borrowings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            book_id INTEGER NOT NULL,
            borrow_date TEXT NOT NULL,
            due_date TEXT NOT NULL,
            returned_at TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE RESTRICT
        )'
    );
}

function seed_defaults(PDO $pdo): void
{
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT OR IGNORE INTO users (username, password_hash, role) VALUES (?, ?, ?)')
        ->execute(['admin', $adminPassword, 'admin']);

    $seedBooks = [
        ['title' => '时间的秩序', 'author' => '卡洛·罗韦利', 'description' => '穿梭量子世界与宇宙边界的时间随笔。', 'total' => 3],
        ['title' => '三体', 'author' => '刘慈欣', 'description' => '科幻史诗，文明碰撞与人性的博弈。', 'total' => 5],
        ['title' => '不能承受的生命之轻', 'author' => '米兰·昆德拉', 'description' => '轻与重、爱情与自由的哲思小说。', 'total' => 2],
    ];

    $stmt = $pdo->prepare('INSERT INTO books (title, author, description, total_copies, available_copies) VALUES (:title, :author, :description, :total, :available)');
    foreach ($seedBooks as $book) {
        $stmt->execute([
            ':title' => $book['title'],
            ':author' => $book['author'],
            ':description' => $book['description'],
            ':total' => $book['total'],
            ':available' => $book['total'],
        ]);
    }
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(?string $role = null): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }

    if ($role !== null && (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== $role)) {
        header('HTTP/1.1 403 Forbidden');
        echo '您没有访问此页面的权限。';
        exit;
    }
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_date(string $date): string
{
    return date('Y-m-d', strtotime($date));
}

