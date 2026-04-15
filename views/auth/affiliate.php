<div class="container py-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 text-center border-warning">
                <i class="fa-solid fa-handshake fa-3x text-warning mb-3"></i>
                <h3 class="text-light">Partner Program</h3>
                <p class="text-secondary small">Earn 10% from every purchase made by users you invite.</p>
                
                <div class="bg-dark p-2 rounded border border-secondary mb-3">
                    <code class="text-info user-select-all"><?= $refLink ?></code>
                </div>
                <small class="text-muted">Copy and share this link</small>
            </div>
            
            <div class="row mt-3">
                 <div class="col-6">
                     <div class="glass-card p-3 text-center">
                         <h2 class="text-success">$<?= $earnings ?></h2>
                         <small class="text-secondary">Earned</small>
                     </div>
                 </div>
                 <div class="col-6">
                     <div class="glass-card p-3 text-center">
                         <h2 class="text-light"><?= $count ?></h2>
                         <small class="text-secondary">Invited</small>
                     </div>
                 </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <h3 class="text-light mb-4">Your Referrals</h3>
            <div class="glass-card p-0">
                <table class="table table-dark table-hover mb-0">
                    <thead><tr><th>User</th><th>Joined</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($referrals as $r): ?>
                        <tr>
                            <td><?= substr($r['email'], 0, 3) ?>***@***</td>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($referrals)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No one yet. Start sharing!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>