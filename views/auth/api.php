<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-light mb-0">Developer API</h2>
            <small class="text-secondary">Integrate CMS-HUB into your applications</small>
        </div>
        <a href="<?= BASE_URL ?>/profile" class="btn btn-secondary">Back to Profile</a>
    </div>

    <div class="row">
        <!-- LEFT: TOKEN MANAGEMENT -->
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4">
                <h5 class="text-info mb-3"><i class="fa-solid fa-key"></i> Authentication</h5>
                <p class="text-light small">Include this token in the <code>Authorization</code> header of your requests.</p>
                
                <div class="bg-black bg-opacity-50 p-3 rounded border border-secondary mb-3">
                    <small class="text-secondary d-block mb-1">Your Bearer Token:</small>
                    <?php $newToken = $flashes['api_token'][0] ?? null; ?>
                    <?php if(!empty($newToken)): ?>
                        <code class="text-info d-block text-break user-select-all"><?= htmlspecialchars((string)$newToken) ?></code>
                        <small class="text-warning d-block mt-2">Copy it now. For security, only a hash is stored.</small>
                    <?php elseif(!empty($user['api_token'])): ?>
                        <span class="text-success">Token generated. Regenerate if you need to copy a new token.</span>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Not generated yet</span>
                    <?php endif; ?>
                </div>

                <form action="<?= BASE_URL ?>/auth/token/generate" method="POST">
                    <?= \Src\Core\Csrf::field() ?>
                    <button class="btn btn-outline-warning w-100">
                        <i class="fa-solid fa-arrows-rotate"></i> <?= empty($user['api_token']) ? 'Generate Token' : 'Revoke & Regenerate' ?>
                    </button>
                </form>
                
                <hr class="border-secondary opacity-25 my-4">
                <div class="alert alert-info bg-opacity-10 border-info small">
                    <i class="fa-solid fa-circle-info"></i> Do not share this token. It grants full access to your account.
                </div>
                <div class="alert alert-warning bg-opacity-10 border-warning small mt-3">
                    <i class="fa-solid fa-triangle-exclamation"></i> API access must comply with Terms and Privacy policy. You are responsible for lawful data processing in your integrations.
                </div>
            </div>
        </div>

        <!-- RIGHT: DOCUMENTATION -->
        <div class="col-md-8">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-3 border-bottom border-secondary bg-dark">
                    <ul class="nav nav-pills card-header-pills" id="apiTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#endpoint1">User Info</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#endpoint2">License Check</a></li>
                    </ul>
                </div>
                
                <div class="tab-content p-4">
                    <!-- ENDPOINT 1 -->
                    <div class="tab-pane fade show active" id="endpoint1">
                        <span class="badge bg-success mb-2">GET</span> <code class="fs-5 text-light">/api/me</code>
                        <p class="text-secondary mt-2">Returns details about your account and wallet balance.</p>
                        <p class="text-secondary small mb-2">Recommended limits: 60 requests/minute per token. Use retries with backoff on 429/5xx.</p>
                        
                        <h6 class="text-light mt-4">Example Request (cURL)</h6>
                        <pre class="bg-black p-3 rounded text-success border border-secondary">curl -X GET <?= BASE_URL ?>/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"</pre>

                        <h6 class="text-light mt-3">Response (JSON)</h6>
                        <pre class="bg-black p-3 rounded text-warning border border-secondary">{
  "id": 123,
  "email": "pilot@cms-hub.ru",
  "balance": "150.00",
  "role": "user"
}</pre>
                    </div>

                    <!-- ENDPOINT 2 -->
                    <div class="tab-pane fade" id="endpoint2">
                        <span class="badge bg-warning text-dark mb-2">POST</span> <code class="fs-5 text-light">/api/license/check</code>
                        <p class="text-secondary mt-2">Validate a license key in your own scripts. Use this to protect your products.</p>
                        <p class="text-secondary small mb-2">Do not send unnecessary personal data. Transmit only license and domain fields required for validation.</p>
                        
                        <h6 class="text-light mt-4">Example Request (PHP)</h6>
                        <pre class="bg-black p-3 rounded text-info border border-secondary">$ch = curl_init('<?= BASE_URL ?>/api/license/check');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'key' => 'CMSHUB-XXXX-XXXX-XXXX',
    'domain' => 'client-site.com'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = curl_exec($ch);</pre>

                        <h6 class="text-light mt-3">Response (JSON)</h6>
                        <pre class="bg-black p-3 rounded text-warning border border-secondary">{
  "valid": true,
  "msg": "Activated for client-site.com"
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>