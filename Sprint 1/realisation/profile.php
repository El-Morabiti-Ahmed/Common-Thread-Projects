<?php
require_once __DIR__ . '/includes/bootstrap.php';
Auth::requireLogin();

$user     = Auth::user();
$sessions = new GameSession();
$stats    = $sessions->statsForPlayer((int) $user['id']);
$recent   = $sessions->recentForPlayer((int) $user['id'], 10);
$progress = Rank::progress((int) $user['points']);

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>

<section class="profile-head card">
    <div>
        <h1><?= e($user['username']) ?></h1>
        <p class="muted">Member since <?= e(date('F j, Y', strtotime($user['created_at']))) ?></p>
    </div>
    <div class="profile-rank">
        <?= Rank::badge($user['player_rank']) ?>
        <strong class="big-number"><?= (int) $user['points'] ?> pts</strong>
    </div>

    <div class="progress-block">
        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="<?= $progress['percent'] ?>">
            <div class="progress-bar rank-bar-<?= e($user['player_rank']) ?>" style="width: <?= $progress['percent'] ?>%"></div>
        </div>
        <p class="muted small">
            <?php if ($progress['next'] !== null): ?>
                <?= $progress['remaining'] ?> more points to reach <?= e(Rank::label($progress['next'])) ?>.
            <?php else: ?>
                You reached the highest rank. Keep winning to stay there.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="grid grid-4 section">
    <div class="card stat"><span class="stat-value"><?= $stats['games'] ?></span><span class="muted">Games played</span></div>
    <div class="card stat"><span class="stat-value win"><?= $stats['wins'] ?></span><span class="muted">Wins</span></div>
    <div class="card stat"><span class="stat-value loss"><?= $stats['losses'] ?></span><span class="muted">Losses</span></div>
    <div class="card stat"><span class="stat-value"><?= $stats['win_rate'] ?>%</span><span class="muted">Win rate</span></div>
</section>

<section class="section">
    <h2>Recent games</h2>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr><th>Game</th><th>Result</th><th class="num">Points</th><th>Played</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e($row['icon']) ?> <?= e($row['game_name']) ?></td>
                        <td><span class="result result-<?= e($row['result']) ?>"><?= $row['result'] === 'win' ? 'Win' : 'Loss' ?></span></td>
                        <td class="num"><?= (int) $row['points_change'] > 0 ? '+' : '' ?><?= (int) $row['points_change'] ?></td>
                        <td class="muted"><?= e(date('M j, H:i', strtotime($row['played_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td colspan="4" class="muted">No games yet. <a href="<?= url('games.php') ?>">Play your first one.</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
