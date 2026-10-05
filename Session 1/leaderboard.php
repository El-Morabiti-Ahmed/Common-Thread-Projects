<?php
require_once __DIR__ . '/includes/bootstrap.php';

$players = (new Player())->leaderboard(50);
$me      = Auth::user();

$pageTitle = 'Leaderboard';
require __DIR__ . '/includes/header.php';
?>

<h1>Leaderboard</h1>
<p class="muted">Top 50 players, sorted by points.</p>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Player</th><th>Rank</th>
                <th class="num">Points</th><th class="num">Wins</th><th class="num">Games</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $i => $p): ?>
                <tr class="<?= $me && (int) $me['id'] === (int) $p['id'] ? 'me' : '' ?>">
                    <td><?= $i + 1 ?></td>
                    <td><?= e($p['username']) ?></td>
                    <td><?= Rank::badge($p['player_rank']) ?></td>
                    <td class="num"><?= (int) $p['points'] ?></td>
                    <td class="num"><?= (int) $p['wins'] ?></td>
                    <td class="num"><?= (int) $p['games'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$players): ?>
                <tr><td colspan="6" class="muted">No players yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
