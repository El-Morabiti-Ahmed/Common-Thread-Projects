<?php
require_once __DIR__ . '/includes/bootstrap.php';

$topPlayers = (new Player())->leaderboard(5);
$games      = (new Game())->all();

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>Play short games.<br>Climb from Iron to Immortal.</h1>
    <p class="lead">
        Pick a game, win or lose, and your rank moves with every session.
        A win is worth <?= Rank::WIN_POINTS ?> points, a loss costs <?= Rank::LOSS_POINTS ?>.
    </p>
    <div class="hero-actions">
        <?php if (Auth::check()): ?>
            <a class="btn btn-primary" href="<?= url('games.php') ?>">Choose a game</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= url('register.php') ?>">Create your account</a>
            <a class="btn btn-ghost" href="<?= url('login.php') ?>">I already have one</a>
        <?php endif; ?>
    </div>

    <ol class="ladder" aria-label="Rank ladder">
        <?php foreach (Rank::all() as $name => $minPoints): ?>
            <li class="ladder-step rank-<?= e($name) ?>">
                <span class="ladder-name"><?= e(Rank::label($name)) ?></span>
                <span class="ladder-points"><?= $minPoints ?>+ pts</span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="section">
    <div class="section-head">
        <h2>Games</h2>
        <a href="<?= url('games.php') ?>">See all games</a>
    </div>

    <div class="grid grid-2">
        <?php foreach ($games as $game): ?>
            <article class="card game-card">
                <div class="game-icon" aria-hidden="true"><?= e($game['icon']) ?></div>
                <div>
                    <h3><?= e($game['name']) ?></h3>
                    <p class="muted"><?= e($game['description']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$games): ?>
            <p class="muted">No games yet. An admin can add some from the admin panel.</p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>Top players</h2>
        <a href="<?= url('leaderboard.php') ?>">Full leaderboard</a>
    </div>

    <div class="card table-wrap">
        <table class="table-compact">
            <thead>
                <tr><th>#</th><th>Player</th><th>Rank</th><th class="num">Points</th></tr>
            </thead>
            <tbody>
                <?php foreach ($topPlayers as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td><?= Rank::badge($p['player_rank']) ?></td>
                        <td class="num"><?= (int) $p['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$topPlayers): ?>
                    <tr><td colspan="4" class="muted">Nobody has played yet. Be the first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
