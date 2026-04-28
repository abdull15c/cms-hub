<div class="container py-5 text-center">
    <div class="card shadow mx-auto" style="max-width: 600px;">
        <div class="card-body py-5">
            <i class="fa-solid fa-check-circle fa-5x text-success mb-3"></i>
            <h2 class="fw-bold text-success">Payment Successful!</h2>
            <p class="lead">Thank you for purchasing <strong><?= htmlspecialchars($product_name) ?></strong>.</p>
            
            <div class="my-4">
                <p class="mb-1 text-muted">Your License Key:</p>
                <div class="license-key fs-4"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <a href="<?= BASE_URL ?>/download/<?= (int)($product_id ?? 0) ?>" class="btn btn-warning btn-lg text-dark shadow mb-3">
                <i class="fa-solid fa-download"></i> Download Files Now
            </a>
            
            <div>
                <a href="<?= BASE_URL ?>/profile" class="text-muted">Go to My Dashboard</a>
            </div>
        </div>
    </div>
</div>
