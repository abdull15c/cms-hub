<div class="container py-5" style="max-width: 800px;">
    <div class="glass-card p-5">
        <h3 class="text-light mb-4">Submit a Request</h3>
        <form action="<?= BASE_URL ?>/tickets/store" method="POST">
            <?= \Src\Core\Csrf::field() ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-secondary">Department</label>
                    <select name="department" class="form-control bg-dark text-light border-secondary">
                        <option value="support">Technical Support</option>
                        <option value="billing">Billing & Refunds</option>
                        <option value="sales">Sales Question</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="text-secondary">Priority</label>
                    <select name="priority" class="form-control bg-dark text-light border-secondary">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High (System Down)</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="text-secondary">Related Order (Optional)</label>
                <select name="transaction_id" class="form-control bg-dark text-light border-secondary">
                    <option value="">-- General Question --</option>
                    <?php foreach($orders as $o): ?>
                        <option value="<?= $o['id'] ?>">Order #<?= $o['id'] ?> - <?= htmlspecialchars($o['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="text-secondary">Subject</label>
                <input type="text" name="subject" class="form-control bg-dark text-light border-secondary" required>
            </div>

            <div class="mb-4">
                <label class="text-secondary">Message</label>
                <textarea name="message" rows="6" class="form-control bg-dark text-light border-secondary" required></textarea>
            </div>

            <button type="submit" class="btn btn-cyber w-100">Open Ticket</button>
        </form>
    </div>
</div>