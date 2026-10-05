<?php /* Sub-menu shown on every admin page */ ?>
<nav class="subnav" aria-label="Admin menu">
    <a class="<?= Helper::activeClass('admin/index.php') ?>" href="<?= url('admin/index.php') ?>">Dashboard</a>
    <a class="<?= Helper::activeClass('admin/games.php') ?>" href="<?= url('admin/games.php') ?>">Games</a>
    <a class="<?= Helper::activeClass('admin/categories.php') ?>" href="<?= url('admin/categories.php') ?>">Categories</a>
    <a class="<?= Helper::activeClass('admin/players.php') ?>" href="<?= url('admin/players.php') ?>">Players</a>
</nav>
