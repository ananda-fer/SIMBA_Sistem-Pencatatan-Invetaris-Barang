    <!-- JS App -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('assets/js/app.js') ?>"></script>
    <?php foreach (($pageJs ?? []) as $js): ?>
    <script src="<?= app_url($js) ?>"></script>
    <?php endforeach; ?>

</div><!-- end app-wrapper -->
</body>
</html>
