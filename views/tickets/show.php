<div class="container py-5">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="glass-card p-3">
                <h6 class="text-info">Ticket Info</h6>
                <hr class="border-secondary opacity-50">
                <p class="mb-1 text-secondary small">ID:</p> <p class="text-light">#<?= $ticket['id'] ?></p>
                <p class="mb-1 text-secondary small">Status:</p> <p class="text-light text-uppercase"><?= $ticket['status'] ?></p>
                <p class="mb-1 text-secondary small">Dept:</p> <p class="text-light"><?= ucfirst($ticket['department']) ?></p>
                <a href="<?= BASE_URL ?>/tickets" class="btn btn-outline-secondary btn-sm w-100 mt-3">&larr; Back</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <h3 class="text-light mb-4"><span class="text-secondary">#<?= $ticket['id'] ?>:</span> <?= htmlspecialchars($ticket['subject']) ?></h3>
            
            <div class="glass-card p-0 overflow-hidden d-flex flex-column" style="min-height: 500px;">
                <div class="flex-grow-1 p-4 bg-black bg-opacity-25" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach($messages as $msg): ?>
                        <div class="mb-4 d-flex <?= $msg['is_admin'] ? 'justify-content-start' : 'justify-content-end' ?>">
                            <div class="p-3 rounded shadow-sm position-relative" style="max-width: 75%; <?= $msg['is_admin'] ? 'background:#2c3e50; color:#ecf0f1;' : 'background:#00f2ea; color:#000;' ?>">
                                <div class="small fw-bold mb-1 opacity-75">
                                    <?= $msg['is_admin'] ? 'Support Agent' : 'You' ?>
                                    <span class="ms-2 fw-normal opacity-50"><?= date('M d H:i', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <div style="white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if($ticket['status'] !== 'closed'): ?>
                <div class="p-3 bg-dark border-top border-secondary">
                    <form action="<?= BASE_URL ?>/tickets/reply/<?= $ticket['id'] ?>" method="POST">
                        <?= \Src\Core\Csrf::field() ?>
                        <textarea name="message" class="form-control bg-black text-light border-secondary mb-2" rows="3" placeholder="Type your reply..."></textarea>
                        <button type="submit" class="btn btn-cyber">Send Reply</button>
                    </form>
                </div>
                <?php else: ?>
                <div class="p-3 bg-danger bg-opacity-10 text-center text-danger">This ticket is closed.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>