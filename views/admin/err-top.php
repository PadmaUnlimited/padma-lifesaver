<?php if (! empty($message) && is_string($message)) : ?>
    <div class="error fade">
        <p><strong>ERROR</strong>: <?= $message; ?></p>
    </div>
<?php endif; ?>