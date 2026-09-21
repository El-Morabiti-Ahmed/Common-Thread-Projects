</main>

<footer class="site-footer">
    <div class="container">
        <p><?= e(APP_NAME) ?> - school project, sprint 1.</p>
    </div>
</footer>

<script src="<?= url('assets/js/main.js') ?>"></script>
<?php foreach (($extraScripts ?? []) as $script): ?>
    <script src="<?= url($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
