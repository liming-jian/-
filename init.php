<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$pdo = get_db();

function flash_message(): ?string
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $message;
}

function set_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function is_admin(): bool
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

