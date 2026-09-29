<?php
/** Stepper checkout. Set $step (1-3) sebelum include. */
$labels = [1 => 'Detail Pesanan', 2 => 'Pembayaran', 3 => 'Konfirmasi'];
?>
<div class="stepper">
    <?php foreach ($labels as $n => $label): ?>
        <?php if ($n > 1): ?><div class="step-line"></div><?php endif; ?>
        <div class="step <?= $n < $step ? 'done' : ($n === $step ? 'active' : '') ?>">
            <span class="dot"><?= $n < $step ? '✓' : $n ?></span><span class="txt"><?= $label ?></span>
        </div>
    <?php endforeach; ?>
</div>
