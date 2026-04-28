<div class="container py-5">
    <div class="glass-card p-5 mx-auto" style="max-width: 600px;">
        <h2 class="text-light mb-4">Setup 2FA Security</h2>
        
        <div class="row">
            <div class="col-md-6 text-center">
                <!-- JS QR Code Generator to avoid external dependencies on server -->
                <div id="qrcode" class="bg-white p-2 rounded mb-3 d-inline-block"></div>
                <p class="text-secondary small">Scan with Google Authenticator</p>
            </div>
            <div class="col-md-6">
                <p class="text-light">Or enter key manually:</p>
                <code class="d-block p-2 bg-dark border border-secondary text-warning mb-4 user-select-all"><?= $secret ?></code>
                
                <form action="<?= BASE_URL ?>/auth/2fa/enable" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <label class="text-secondary mb-1">Verification Code</label>
                    <input type="text" name="code" class="form-control bg-dark text-light border-info mb-3" placeholder="123456" required>
                    <button type="submit" class="btn btn-cyber w-100">Activate 2FA</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- QR Lib -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script nonce="<?= CSP_NONCE ?>">
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= $qrUrl ?>",
        width: 150,
        height: 150
    });
</script>