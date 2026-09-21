<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireAdmin();

$gameModel  = new Game();
$categories = (new GameCategory())->all();
$errors     = [];

// Form values (empty for "add", filled for "edit")
$form = [
    'id' => 0, 'name' => '', 'slug' => '', 'description' => '',
    'category_id' => '', 'icon' => '🎲', 'is_active' => 1,
];
if (isset($_GET['edit'])) {
    $found = $gameModel->find((int) $_GET['edit']);
    if ($found !== null) {
        $form = $found;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';

    } elseif ($action === 'delete') {
        try {
            $gameModel->delete($id);
            Helper::flash('success', 'Game deleted.');
        } catch (DomainException $e) {
            Helper::flash('error', $e->getMessage());
        }
        Helper::redirect('admin/games.php');

    } elseif ($action === 'toggle') {
        $gameModel->toggleActive($id);
        Helper::flash('success', 'Game visibility updated.');
        Helper::redirect('admin/games.php');

    } elseif ($action === 'save') {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'slug'        => strtolower(trim($_POST['slug'] ?? '')),
            'description' => trim($_POST['description'] ?? ''),
            'icon'        => trim($_POST['icon'] ?? ''),
            'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];
        $errors = $gameModel->validate($data);

        if (!$errors) {
            try {
                if ($id > 0) {
                    $gameModel->update($id, $data);
                    Helper::flash('success', 'Game updated.');
                } else {
                    $gameModel->create($data);
                    Helper::flash('success', 'Game added.');
                }
                Helper::redirect('admin/games.php');
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }
        // show what the admin typed again
        $form = array_merge($form, $data, ['id' => $id]);
    }
}

$games = $gameModel->all(null, false);   // false = include inactive games

$pageTitle = 'Manage games';
require __DIR__ . '/../includes/header.php';
?>

<h1>Admin</h1>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="admin-grid">
    <section class="card">
        <h2><?= $form['id'] ? 'Edit game' : 'Add a game' ?></h2>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="60" required>

            <label for="slug">Slug <span class="hint">(= JS file name in assets/js/games/)</span></label>
            <input type="text" id="slug" name="slug" value="<?= e($form['slug']) ?>" maxlength="60" required>

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">No category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"
                        <?= (int) $form['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="icon">Icon <span class="hint">(one emoji)</span></label>
            <input type="text" id="icon" name="icon" value="<?= e($form['icon']) ?>" maxlength="8" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="255"><?= e($form['description']) ?></textarea>

            <label class="check">
                <input type="checkbox" name="is_active" value="1" <?= (int) $form['is_active'] ? 'checked' : '' ?>>
                Visible to players
            </label>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $form['id'] ? 'Save changes' : 'Add game' ?></button>
                <?php if ($form['id']): ?>
                    <a class="btn btn-ghost" href="<?= url('admin/games.php') ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section>
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Game</th><th>Category</th><th>Status</th><th class="num">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                        <tr>
                            <td>
                                <?= e($game['icon']) ?> <strong><?= e($game['name']) ?></strong><br>
                                <span class="muted small"><?= e($game['slug']) ?>.js</span>
                                <?php if (!Game::scriptExists($game['slug'])): ?>
                                    <span class="tag tag-warn">JS file missing</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($game['category_name'] ?? '-') ?></td>
                            <td><?= (int) $game['is_active'] ? 'Visible' : 'Hidden' ?></td>
                            <td class="num actions">
                                <a class="btn btn-ghost btn-small" href="<?= url('admin/games.php?edit=' . (int) $game['id']) ?>">Edit</a>

                                <form method="post" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $game['id'] ?>">
                                    <button class="btn btn-ghost btn-small" type="submit">
                                        <?= (int) $game['is_active'] ? 'Hide' : 'Show' ?>
                                    </button>
                                </form>

                                <form method="post" class="inline-form"
                                      data-confirm="Delete <?= e($game['name']) ?>? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $game['id'] ?>">
                                    <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$games): ?>
                        <tr><td colspan="4" class="muted">No games yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="muted small">A new game only works once its JavaScript file exists in assets/js/games/.</p>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
