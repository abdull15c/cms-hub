<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-light"><i class="fa-solid fa-cloud-arrow-up text-info"></i> System Updater</h2>
        <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-secondary">Back</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="glass-card p-5 border-info">
                <h4 class="text-light mb-3">Install Update</h4>
                <p class="text-secondary mb-4">Upload a <code>.zip</code> file containing the new project files. The system will automatically replace old files and run migrations.</p>
                
                <form action="<?= BASE_URL ?>/admin/update/run" method="POST" enctype="multipart/form-data">
                    <?= \Src\Core\Csrf::field() ?>
                    
                    <div class="mb-4">
                        <label class="form-label text-warning">Update Package (.zip)</label>
                        <input type="file" name="update_file" class="form-control bg-dark text-light border-secondary" accept=".zip" required>
                    </div>

                    <div class="alert alert-warning bg-opacity-10 border-warning small">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        <strong>Backup Recommended!</strong><br>
                        This process will overwrite system files. Your <code>.env</code> and <code>uploads</code> are safe.
                    </div>

                    <button type="submit" class="btn btn-cyber w-100 btn-lg" onclick="return confirm('Start system update? Site might be unresponsive for a few seconds.');">
                        <i class="fa-solid fa-rocket"></i> Install Update
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="glass-card p-4">
                <h5 class="text-light">How to prepare an update?</h5>
                <ol class="text-secondary small mt-3" style="line-height: 1.8;">
                    <li>Make changes on your local PC.</li>
                    <li>Select all files in your project folder (Ctrl+A).</li>
                    <li>Archive them into <code>update.zip</code>.</li>
                    <li><strong>Database Changes:</strong> If you added tables/columns, create a file named <code>migration.sql</code> inside the ZIP root with the SQL commands.</li>
                    <li>Upload here.</li>
                </ol>
                <hr class="border-secondary opacity-25">
                <div class="text-muted small">
                    Current PHP Version: <?= phpversion() ?><br>
                    Upload Limit: <?= ini_get('upload_max_filesize') ?>
                </div>
            </div>
        </div>
    </div>
</div>