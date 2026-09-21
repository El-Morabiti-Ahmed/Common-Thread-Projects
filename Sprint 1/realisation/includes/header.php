<?php
/**
 * Top of every page. The page sets $pageTitle before including it.
 */
$currentUser = Auth::user();
$pageTitle   = $pageTitle ?? APP_NAME;
$flashes     = Helper::pullFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= url('index.php') ?>">
            <span class="brand-mark">♟</span> <?= e(APP_NAME) ?>
        </a>

        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="nav-links">☰</button>

        <nav class="nav-links" id="nav-links">
            <a class="<?= Helper::activeClass('index.php') ?>" href="<?= url('index.php') ?>">Home</a>
            <a class="<?= Helper::activeClass('games.php') ?>" href="<?= url('games.php') ?>">Games</a>
            <a class="<?= Helper::activeClass('leaderboard.php') ?>" href="<?= url('leaderboard.php') ?>">Leaderboard</a>

            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a class="<?= strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ? 'active' : '' ?>"
                       href="<?= url('admin/index.php') ?>">Admin</a>
                <?php endif; ?>
                <a class="<?= Helper::activeClass('profile.php') ?>" href="<?= url('profile.php') ?>">
                    <?= e($currentUser['username']) ?>
                </a>
                <form method="post" action="<?= url('logout.php') ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-button">Log out</button>
                </form>
            <?php else: ?>
                <a class="<?= Helper::activeClass('login.php') ?>" href="<?= url('login.php') ?>">Log in</a>
                <a class="btn btn-primary btn-small" href="<?= url('register.php') ?>">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container page">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
