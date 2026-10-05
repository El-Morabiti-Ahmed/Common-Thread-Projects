<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::check()) {
    Helper::redirect('games.php');
}

$errors = [];
$old    = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm         = $_POST['password_confirm'] ?? '';

    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $players = new Player();
        $errors  = $players->validateRegistration($old['username'], $old['email'], $password, $confirm);

        if (!$errors) {
            try {
                $id = $players->register($old['username'], $old['email'], $password);
                Auth::login($players->findById($id));
                Helper::flash('success', 'Welcome, ' . $old['username'] . '! You start at Iron rank.');
                Helper::redirect('games.php');
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-box card">
    <h1>Create your account</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
        <?= csrf_field() ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($old['username']) ?>"
               maxlength="20" autocomplete="username" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
               maxlength="100" autocomplete="email" required>

        <label for="password">Password <span class="hint">(8 characters minimum)</span></label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>

        <label for="password_confirm">Repeat password</label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>

        <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="muted center">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
