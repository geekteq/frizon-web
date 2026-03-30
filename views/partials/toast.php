<?php
$successMsg = flash('success');
$errorMsg = flash('error');
$infoMsg = flash('info');
?>
<?php if ($successMsg): ?>
    <div class="toast toast--success" role="alert"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="toast toast--error" role="alert"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>
<?php if ($infoMsg): ?>
    <div class="toast toast--info" role="alert"><?= htmlspecialchars($infoMsg) ?></div>
<?php endif; ?>
