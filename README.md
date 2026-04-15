# Market

Local launch on Laragon:

1. Copy `.env.example` to `.env` if needed.
2. Set `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Run migrations if database is fresh:
   - `php deployment/migrations/20260331_hardening.php`
   - `php deployment/migrations/20260414_analytics.php`
   - `php deployment/migrations/20260414_product_translations.php`
   - `php deployment/migrations/20260414_product_status.php`
4. Start local stack:
   - `powershell -ExecutionPolicy Bypass -File "tools/start-local.ps1"`
5. Open:
   - `http://market.test`
   - `http://market.test/admin/dashboard`

Readiness check only:

- `php tools/ready_check.php`
- `php deployment/backup.php`

Current local defaults expected by the project:

- PHP: `C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe`
- MySQL: `localhost:3306`
- Apache vhost: `market.test -> C:/laragon/www/market/public`

Notes:

- `ready_check.php` creates missing runtime directories under `storage/` and `public/uploads/`.
- `ready_check.php` also prepares the backup directory from `BACKUP_DIR` when it is missing.
- `ready_check.php` validates the worker tuning envs: `WORKER_SLEEP_SECONDS`, `WORKER_MAX_JOBS`, and `WORKER_MAX_RUNTIME`.
- SMTP placeholder credentials will be reported as not ready, but the site can still boot.
- Public catalog shows only `published` products; drafts remain admin-only.
- Linux deploy, cron/worker autostart, heartbeat monitoring, and backup/restore notes are collected in `deployment/PRODUCTION_CHECKLIST.md`.
