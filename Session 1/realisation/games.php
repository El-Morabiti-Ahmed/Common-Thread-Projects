<?php
require_once __DIR__ . '/includes/bootstrap.php';

$categories = (new GameCategory())->all();

// ?category=2 filters the list; 0 or missing = all games
$categoryId = (int) ($_GET['category'] ?? 0);
$games      = (new Game())->all($categoryId > 0 ? $categoryId : null);

$pageTitle = 'Games';
require __DIR__ . '/includes/header.php';
?>

<h1>Games</h1>
<p class="muted">Every finished game is saved: win +<?= Rank::WIN_POINTS ?> points, loss -<?= Rank::LOSS_POINTS ?> point.</p>

<div class="chips" aria-label="Filter by category">
    <a class="chip <?= $categoryId === 0 ? 'active' : '' ?>" href="<?= url('games.php') ?>">All</a>
    <?php foreach ($categories as $category): ?>
        <a class="chip <?= $categoryId === (int) $category['id'] ? 'active' : '' ?>"
           href="<?= url('games.php?category=' . (int) $category['id']) ?>">
            <?= e($category['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid grid-2">
    <?php foreach ($games as $game): ?>
        <article class="card game-card">
            <div class="game-icon" aria-hidden="true"><?= e($game['icon']) ?></div>
            <div class="game-info">
                <h3><?= e($game['name']) ?></h3>
                <?php if ($game['category_name']): ?>
                    <span class="tag"><?= e($game['category_name']) ?></span>
                <?php endif; ?>
                <p class="muted"><?= e($game['description']) ?></p>

                <?php if (!Game::scriptExists($game['slug'])): ?>
                    <span class="muted small">Coming soon</span>
                <?php elseif (Auth::check()): ?>
                    <a class="btn btn-primary btn-small" href="<?= url('play.php?id=' . (int) $game['id']) ?>">Play</a>
                <?php else: ?>
                    <a class="btn btn-ghost btn-small" href="<?= url('login.php') ?>">Log in to play</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$games): ?>
    <p class="muted">No games in this category yet.</p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
