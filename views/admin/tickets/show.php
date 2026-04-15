<div class="container py-5">
    <div class="row">
        <!-- Sidebar Info -->
        <div class="col-md-4">
            <div class="glass-card p-3 mb-3">
                <h5 class="text-info">Customer Details</h5>
                <p class="mb-1 text-light"><i class="fa-solid fa-user me-2"></i> <?= htmlspecialchars($ticket['email']) ?></p>
                <?php if($ticket['product_title']): ?>
                    <hr class="border-secondary">
                    <p class="mb-1 text-warning small">RELATED PURCHASE:</p>
                    <p class="text-light"><?= htmlspecialchars($ticket['product_title']) ?> ($<?= $ticket['amount'] ?>)</p>
                <?php endif; ?>
            </div>
            
            <form action="<?= BASE_URL ?>/admin/tickets/reply/<?= $ticket['id'] ?>" method="POST" class="glass-card p-3">
                <?= \Src\Core\Csrf::field() ?>
                <h5 class="text-info mb-3">Reply & Action</h5>
                
                <div class="mb-3">
                    <label class="text-secondary small">Set Status</label>
                    <select name="status" class="form-control bg-dark text-light border-secondary">
                        <option value="answered" selected>Answered (Wait for user)</option>
                        <option value="closed">Close Ticket</option>
                        <option value="open">Open (Investigating)</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="generateReply(this)">
                        <i class="fa-solid fa-robot"></i> AI Suggestion
                    </button>
                </div>
                <textarea id="replyBox" name="message" class="form-control bg-black text-light border-secondary" rows="5" placeholder="Internal note or reply..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-cyber w-100">Update Ticket</button>
            </form>
        </div>

        <!-- Chat Stream -->
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-light"><?= htmlspecialchars($ticket['subject']) ?></h3>
                <span class="badge bg-secondary">#<?= $ticket['id'] ?></span>
            </div>

            <div class="glass-card p-4 bg-black bg-opacity-25" style="max-height: 700px; overflow-y: auto;">
                <?php foreach($messages as $msg): ?>
                    <div class="mb-4 d-flex <?= $msg['is_admin'] ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="p-3 rounded border border-opacity-25 <?= $msg['is_admin'] ? 'bg-primary bg-opacity-10 border-primary text-end' : 'bg-dark border-secondary' ?>" style="max-width: 80%;">
                            <div class="small mb-1 <?= $msg['is_admin'] ? 'text-primary' : 'text-warning' ?>">
                                <?= $msg['is_admin'] ? 'Support Staff' : htmlspecialchars($msg['email']) ?>
                            </div>
                            <div class="text-light" style="white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                            <div class="text-muted small mt-1" style="font-size: 0.7rem;"><?= $msg['created_at'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div><script>
async function generateReply(btn) {
    // Get ticket details from DOM (we need to be careful with selectors)
    // We assume the H3 title is Subject, and first message is User Message.
    // Let's pass them via PHP data attributes to be safe.
    
    const subject = "<?= addslashes($ticket['subject']) ?>";
    const userName = "<?= addslashes($ticket['email']) ?>"; // Using email as name for now
    
    // Find first user message content (simple heuristic)
    // This is tricky on client side. Let's just grab the subject for now, or the last user message.
    // Actually, let's grab the Subject + "Please check chat history" context
    
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/admin/tickets/ai_reply', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                subject: subject, 
                message: "Based on subject: " + subject, 
                user: userName,
                csrf_token: csrfToken
            })
        });
        
        const json = await res.json();
        if(json.status === 'success') {
            document.getElementById('replyBox').value = json.reply;
        } else {
            alert('AI Error: ' + json.error);
        }
    } catch(e) {
        alert('Network Error');
    }

    btn.innerHTML = originalText;
    btn.disabled = false;
}
</script>