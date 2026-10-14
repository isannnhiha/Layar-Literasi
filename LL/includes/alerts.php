<?php
/**
 * Flash Alert Display Component
 */
$flash = getFlash();
if ($flash): 
?>
<div class="container my-3">
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
        <?php if ($flash['type'] === 'success'): ?>
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <?php elseif ($flash['type'] === 'danger'): ?>
            <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
        <?php elseif ($flash['type'] === 'warning'): ?>
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <?php else: ?>
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <?php endif; ?>
        <div class="flex-grow-1">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>
