<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::check()) {
    Helper::redirect('games.php');
}

$error      = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $player = (new Player())->authenticate($identifier, $password);

        if ($player !== null) {
            Auth::login($player);
            Helper::flash('success', 'Welcome back, ' . $player['username'] . '.');
            Helper::redirect('games.php');
        }
        $error = 'Wrong username/email or password.';
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-box card">
    <h1>Log in</h1>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label for="identifier">Username or email</label>
        <input type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>"
               autocomplete="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>

    <p class="muted center">No account yet? <a href="<?= url('register.php') ?>">Sign up</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
