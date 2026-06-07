  </main><!-- end main-content -->
</div><!-- end app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= app_url('assets/js/app.js') ?>"></script>
<?php if (!empty($pageJs)): foreach ($pageJs as $js): ?>
<script src="<?= app_url($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
