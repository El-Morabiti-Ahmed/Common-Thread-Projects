<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$game = (new Game())->find((int) ($_GET['id'] ?? 0));

if ($game === null || !(int) $game['is_active'] || !Game::scriptExists($game['slug'])) {
    Helper::flash('error', 'That game is not available.');
    Helper::redirect('games.php');
}

// One-time token: the result can only be saved with it (see api/save_session.php)
$token = PlayToken::issue((int) $game['id']);

$pageTitle    = $game['name'];
$extraScripts = ['assets/js/play.js', 'assets/js/games/' . $game['slug'] . '.js'];
require __DIR__ . '/includes/header.php';
?>

<div class="play-head">
    <div>
        <h1><?= e($game['icon']) ?> <?= e($game['name']) ?></h1>
        <p class="muted"><?= e($game['description']) ?></p>
    </div>

    <div class="hud card">
        <span id="hud-rank"><?= Rank::badge($user['player_rank']) ?></span>
        <strong><span id="hud-points"><?= (int) $user['points'] ?></span> pts</strong>
    </div>
</div>

<div id="game-root" class="game-root card"
     data-game-id="<?= (int) $game['id'] ?>"
     data-endpoint="<?= e(url('api/save_session.php')) ?>"
     data-slug="<?= e($game['slug']) ?>"
     data-token="<?= e($token) ?>">

    <div class="game-stage"></div>

    <div class="result-overlay" hidden>
        <div class="result-card" role="dialog" aria-live="polite">
            <h2 class="result-title"></h2>
            <p class="result-points"></p>
            <p class="result-rank"></p>
            <div class="result-actions">
                <button type="button" class="btn btn-primary result-action"></button>
                <a class="btn btn-ghost" href="<?= url('games.php') ?>">All games</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
