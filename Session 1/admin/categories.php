<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireAdmin();

$categoryModel = new GameCategory();
$errors        = [];
$form          = ['id' => 0, 'name' => '', 'description' => ''];

if (isset($_GET['edit'])) {
    $found = $categoryModel->find((int) $_GET['edit']);
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
        $categoryModel->delete($id);
        Helper::flash('success', 'Category deleted. Its games are now uncategorised.');
        Helper::redirect('admin/categories.php');

    } elseif ($action === 'save') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $errors      = $categoryModel->validate($name, $description);

        if (!$errors) {
            try {
                if ($id > 0) {
                    $categoryModel->update($id, $name, $description);
                    Helper::flash('success', 'Category updated.');
                } else {
                    $categoryModel->create($name, $description);
                    Helper::flash('success', 'Category added.');
                }
                Helper::redirect('admin/categories.php');
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $form = ['id' => $id, 'name' => $name, 'description' => $description];
    }
}

$categories = $categoryModel->allWithGameCount();

$pageTitle = 'Manage categories';
require __DIR__ . '/../includes/header.php';
?>

<h1>Admin</h1>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="admin-grid">
    <section class="card">
        <h2><?= $form['id'] ? 'Edit category' : 'Add a category' ?></h2>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">

            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="50" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="255"><?= e($form['description']) ?></textarea>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $form['id'] ? 'Save changes' : 'Add category' ?></button>
                <?php if ($form['id']): ?>
                    <a class="btn btn-ghost" href="<?= url('admin/categories.php') ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card table-wrap">
        <table>
            <thead><tr><th>Category</th><th class="num">Games</th><th class="num">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <strong><?= e($category['name']) ?></strong><br>
                            <span class="muted small"><?= e($category['description']) ?></span>
                        </td>
                        <td class="num"><?= (int) $category['game_count'] ?></td>
                        <td class="num actions">
                            <a class="btn btn-ghost btn-small" href="<?= url('admin/categories.php?edit=' . (int) $category['id']) ?>">Edit</a>
                            <form method="post" class="inline-form"
                                  data-confirm="Delete <?= e($category['name']) ?>? Its games will become uncategorised.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                <button class="btn btn-danger btn-small" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$categories): ?>
                    <tr><td colspan="3" class="muted">No categories yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
