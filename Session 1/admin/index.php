<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireAdmin();

$sessions = new GameSession();
$counts   = [
    'Players'  => (new Player())->countAll(),
    'Games'    => (new Game())->countAll(),
    'Sessions' => $sessions->countAll(),
];
$byGame = $sessions->statsByGame();
$recent = $sessions->recentAll(10);

$pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>

<h1>Admin</h1>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<section class="grid grid-3">
    <?php foreach ($counts as $label => $value): ?>
        <div class="card stat">
            <span class="stat-value"><?= $value ?></span>
            <span class="muted"><?= e($label) ?></span>
        </div>
    <?php endforeach; ?>
</section>

<section class="section">
    <h2>Plays per game</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Game</th><th class="num">Plays</th><th class="num">Wins</th></tr></thead>
            <tbody>
                <?php foreach ($byGame as $row): ?>
                    <tr>
                        <td><?= e($row['icon']) ?> <?= e($row['name']) ?></td>
                        <td class="num"><?= (int) $row['plays'] ?></td>
                        <td class="num"><?= (int) $row['wins'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <h2>Latest sessions</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Player</th><th>Game</th><th>Result</th><th class="num">Points</th><th>Played</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e($row['username']) ?></td>
                        <td><?= e($row['game_name']) ?></td>
                        <td><span class="result result-<?= e($row['result']) ?>"><?= $row['result'] === 'win' ? 'Win' : 'Loss' ?></span></td>
                        <td class="num"><?= (int) $row['points_change'] > 0 ? '+' : '' ?><?= (int) $row['points_change'] ?></td>
                        <td class="muted"><?= e(date('M j, H:i', strtotime($row['played_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td colspan="5" class="muted">No sessions recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
