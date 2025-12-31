<?php
require_once __DIR__ . '/../init.php';
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>图书管理系统</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="logo">阅书·云廊</div>
    <nav class="nav">
        <a href="/index.php">首页</a>
        <a href="/books.php">图书馆</a>
        <?php if (is_admin()): ?>
            <a href="/admin.php">管理员</a>
        <?php endif; ?>
    </nav>
    <div class="auth">
        <?php if (is_logged_in()): ?>
            <span class="user-pill">👋 <?php echo escape_html(current_user()['username']); ?></span>
            <a class="ghost" href="/logout.php">退出</a>
        <?php else: ?>
            <a href="/login.php">登录</a>
            <a class="ghost" href="/register.php">注册</a>
        <?php endif; ?>
    </div>
</header>
<main class="page">
