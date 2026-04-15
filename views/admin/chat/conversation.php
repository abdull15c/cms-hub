<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light">Chat: <?= htmlspecialchars($thread['product_title']) ?></h2>
        <a href="<?= BASE_URL ?>/admin/chat" class="btn btn-secondary">Back to Inbox</a>
    </div>

    <div class="glass-card p-0 overflow-hidden d-flex flex-column" style="height: 600px;">
        <div class="bg-dark p-3 border-bottom border-secondary">
            User: <strong class="text-info"><?= $thread['email'] ?></strong>
        </div>
        
        <div class="flex-grow-1 p-4 bg-black bg-opacity-25" style="overflow-y: auto; display: flex; flex-direction: column;">
            <?php foreach($messages as $msg): ?>
                <div class="mb-3 p-3 rounded <?= $msg['sender_type']=='admin' ? 'bg-primary align-self-end text-end' : 'bg-secondary align-self-start' ?>" style="max-width: 70%;">
                    <div class="text-white"><?= htmlspecialchars($msg['message']) ?></div>
                    <small class="text-light opacity-50" style="font-size: 0.7rem;"><?= $msg['created_at'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-3 bg-dark border-top border-secondary">
            <!-- FIXED: Added CSRF Token -->
            <form action="<?= BASE_URL ?>/admin/chat/reply/<?= $thread['id'] ?>" method="POST" class="d-flex gap-2">
                <?= \Src\Core\Csrf::field() ?>
                <input type="text" name="message" class="form-control bg-black text-light border-secondary" placeholder="Type answer..." required>
                <button type="submit" class="btn btn-cyber">Send</button>
            </form>
        </div>
    </div>
</div>