<?php
// PROFESSIONAL INSTALLER WIZARD
$envExists = file_exists('../../.env');
$lockExists = file_exists('../../storage/.installed.lock');
if ($envExists || $lockExists) { exit("<div style='color:white;background:#0b0f19;padding:50px;text-align:center;font-family:sans-serif;'>System already installed.</div>"); }
$setupToken = (string)($_ENV['INSTALLER_SETUP_TOKEN'] ?? getenv('INSTALLER_SETUP_TOKEN') ?: getenv('INSTALLER_TOKEN') ?: '');
$requestToken = (string)($_GET['setup_token'] ?? ($_SERVER['HTTP_X_SETUP_TOKEN'] ?? ''));
$allowInstallerRaw = strtolower(trim((string)($_ENV['ENABLE_WEB_INSTALLER'] ?? getenv('ENABLE_WEB_INSTALLER') ?: '')));
$allowInstaller = in_array($allowInstallerRaw, ['1', 'true', 'yes', 'on'], true);
if (!$allowInstaller || $setupToken === '' || !hash_equals($setupToken, $requestToken)) {
    http_response_code(403);
    exit("<div style='color:white;background:#0b0f19;padding:50px;text-align:center;font-family:sans-serif;'>Installer is locked.</div>");
}
$step = $_GET['step'] ?? 1;
$defaultDbHost = (string)($_ENV['INSTALL_DB_HOST'] ?? getenv('INSTALL_DB_HOST') ?: $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
$defaultDbPort = (string)($_ENV['INSTALL_DB_PORT'] ?? getenv('INSTALL_DB_PORT') ?: $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
$defaultDbName = (string)($_ENV['INSTALL_DB_NAME'] ?? getenv('INSTALL_DB_NAME') ?: $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'cmshub_db');
$defaultDbUser = (string)($_ENV['INSTALL_DB_USER'] ?? getenv('INSTALL_DB_USER') ?: $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
$defaultAdminEmail = (string)($_ENV['INSTALL_ADMIN_EMAIL'] ?? getenv('INSTALL_ADMIN_EMAIL') ?: 'admin@example.com');
$defaultAppUrl = (string)($_ENV['INSTALL_APP_URL'] ?? getenv('INSTALL_APP_URL') ?: $_ENV['APP_URL'] ?? getenv('APP_URL') ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));
$hasPresetSecrets = ((string)($_ENV['INSTALL_DB_PASS'] ?? getenv('INSTALL_DB_PASS') ?: '') !== '')
    || ((string)($_ENV['INSTALL_ADMIN_PASSWORD'] ?? getenv('INSTALL_ADMIN_PASSWORD') ?: '') !== '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS-HUB Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.6);
            --accent: #00f2ea;
            --text-main: #e2e8f0;
        }
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            font-family: 'Outfit', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgba(112, 0, 255, 0.1) 0%, transparent 40%);
        }
        .glass-card { 
            background: var(--card-bg); 
            border: 1px solid rgba(255,255,255,0.1); 
            backdrop-filter: blur(15px); 
            border-radius: 24px; 
            width: 500px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .form-control { 
            background: rgba(0,0,0,0.3); 
            border-color: rgba(255,255,255,0.1); 
            color: #fff; 
            padding: 12px;
            border-radius: 12px;
        }
        .form-control:focus { 
            background: rgba(0,0,0,0.5); 
            color: #fff; 
            border-color: var(--accent); 
            box-shadow: 0 0 15px rgba(0, 242, 234, 0.2); 
        }
        .btn-cyber {
            background: linear-gradient(90deg, #00f2ea, #00a8c6);
            border: none;
            color: #000;
            font-weight: 700;
            padding: 12px;
            border-radius: 12px;
            transition: 0.3s;
        }
        .btn-cyber:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 242, 234, 0.3);
            color: #000;
        }
        .step-indicator {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; color: #aaa;
        }
        .step-indicator.active {
            background: var(--accent);
            color: #000;
            font-weight: bold;
        }
        label { margin-bottom: 5px; font-size: 0.9rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="glass-card p-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white mb-1">CMS-HUB</h2>
            <p class="text-secondary small">Installation Wizard</p>
            
            <div class="d-flex justify-content-center gap-2 mt-3">
                <div class="step-indicator <?= $step==1?'active':'' ?>">1</div>
                <div class="step-indicator <?= $step==2?'active':'' ?>">2</div>
            </div>
        </div>

        <?php if($step == 1): ?>
            <h5 class="mb-3 text-info"><i class="fa-solid fa-server me-2"></i> System Check</h5>
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-2 p-2 rounded bg-black bg-opacity-25 border border-secondary border-opacity-10">
                    <span>PHP Version >= 7.4</span> 
                    <span class="<?= phpversion() >= 7.4 ? 'text-success' : 'text-danger' ?>">
                        <?= phpversion() >= 7.4 ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2 p-2 rounded bg-black bg-opacity-25 border border-secondary border-opacity-10">
                    <span>PDO Extension</span> 
                    <span class="<?= extension_loaded('pdo') ? 'text-success' : 'text-danger' ?>">
                        <?= extension_loaded('pdo') ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2 p-2 rounded bg-black bg-opacity-25 border border-secondary border-opacity-10">
                    <span>Write Permissions</span> 
                    <span class="<?= is_writable('../../') ? 'text-success' : 'text-danger' ?>">
                        <?= is_writable('../../') ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>' ?>
                    </span>
                </div>
            </div>
            <a href="?step=2&amp;setup_token=<?= urlencode($requestToken) ?>" class="btn btn-cyber w-100">Next Step <i class="fa-solid fa-arrow-right ms-2"></i></a>

        <?php elseif($step == 2): ?>
            <form action="process.php" method="POST">
                <input type="hidden" name="setup_token" value="<?= htmlspecialchars($requestToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($hasPresetSecrets): ?>
                    <div class="mb-3 small text-info bg-black bg-opacity-25 border border-secondary border-opacity-10 rounded p-3">
                        Preset installer secrets were found in environment variables. You can leave password fields blank to use them.
                    </div>
                <?php endif; ?>
                <h5 class="mb-3 text-info"><i class="fa-solid fa-database me-2"></i> Database</h5>
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label>DB Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($defaultDbHost, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>DB Port</label>
                        <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($defaultDbPort, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label>DB Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($defaultDbName, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>DB User</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($defaultDbUser, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>DB Password</label>
                        <input type="password" name="db_pass" class="form-control" placeholder="Leave blank to use env preset">
                    </div>
                </div>

                <h5 class="mb-3 mt-4 text-warning"><i class="fa-solid fa-user-shield me-2"></i> Admin Account</h5>
                <div class="mb-2">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($defaultAdminEmail, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-2">
                    <label>Admin Password</label>
                    <input type="password" name="admin_pass" class="form-control" placeholder="Leave blank to use env preset">
                </div>
                
                <div class="mb-4 mt-3">
                    <label>Site URL</label>
                    <input type="text" name="app_url" class="form-control" value="<?= htmlspecialchars($defaultAppUrl, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <button type="submit" class="btn btn-cyber w-100">Install System <i class="fa-solid fa-rocket ms-2"></i></button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
