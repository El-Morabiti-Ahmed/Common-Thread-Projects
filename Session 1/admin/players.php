<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireAdmin();

$players = (new Player())->allWithStats();

$pageTitle = 'Players';
require __DIR__ . '/../includes/header.php';
?>

<h1>Admin</h1>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<p class="muted">
    Roles are changed in MySQL Workbench:
    <code>UPDATE player SET role = 'admin' WHERE username = 'name';</code>
</p>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Player</th><th>Email</th><th>Role</th><th>Rank</th>
                <th class="num">Points</th><th class="num">Games</th><th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
                <tr>
                    <td><strong><?= e($p['username']) ?></strong></td>
                    <td class="muted"><?= e($p['email']) ?></td>
                    <td><span class="tag <?= $p['role'] === 'admin' ? 'tag-accent' : '' ?>"><?= e($p['role']) ?></span></td>
                    <td><?= Rank::badge($p['player_rank']) ?></td>
                    <td class="num"><?= (int) $p['points'] ?></td>
                    <td class="num"><?= (int) $p['games'] ?></td>
                    <td class="muted"><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
