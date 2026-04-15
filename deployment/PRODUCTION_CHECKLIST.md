# Production-Ready Checklist

1. Mail

- Fill in `MAIL_FROM_NAME`, `MAIL_FROM_ADDRESS`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USER`, `SMTP_PASS` in `.env`.
- Keep `SMTP_VERIFY_PEER=true` unless you knowingly use a private CA.
- Run `php tools/ready_check.php` and confirm that SMTP is no longer reported as placeholder or empty.
- Send a real test from `/admin/settings` using the "SMTP Test" action before opening the site to users.

2. Cron and worker autostart

- Preferred on Linux with systemd:
  `sudo bash deployment/install_systemd.sh /var/www/market /usr/bin/php www-data`
- Check services:
  `sudo systemctl status market-worker.service`
  `sudo systemctl status market-cron.timer`
- Follow logs during the first boot:
  `sudo journalctl -u market-worker.service -f`
  `sudo journalctl -u market-cron.service -f`
- Supervisor worker alternative:
  `sudo bash deployment/install_worker.sh /var/www/market /usr/bin/php www-data`
- Plain cron alternative:
  copy the command from `deployment/cron_entry.txt`.
- Shared hosting note:
  `public/cron.php` processes up to 10 jobs per minute, so it is acceptable as a fallback but slower than a dedicated worker.
- New worker tuning knobs in `.env`:
  `WORKER_SLEEP_SECONDS`, `WORKER_MAX_JOBS`, `WORKER_MAX_RUNTIME`
- Runtime health after deploy should show both cron and worker heartbeats in `/admin/pulse`.

3. Backups and restore

- Create a backup manually:
  `php deployment/backup.php`
- Use a custom destination or retention:
  `php deployment/backup.php --path=/var/backups/market --keep-days=14`
- If `mysqldump` or `mysql` are outside PATH, point `MYSQLDUMP_BIN` and `MYSQL_BIN` in `.env` to the full binary paths.
- Daily Linux cron example:
  `15 2 * * * cd /var/www/market && /usr/bin/php deployment/backup.php >> storage/logs/backup.log 2>&1`
- Restore from a snapshot:
  `php deployment/restore.php --from=storage/backups/20260414_020000 --force`
- Restore should be done with the app in maintenance mode and after taking a fresh emergency snapshot.

4. Deploy notes for hosting/Linux

- Set web root to `/public`, not to the repository root.
- Use the same PHP version for both web and CLI, or cron/worker behaviour can diverge.
- Ensure write access for `storage/` and `public/uploads/`.
- Ensure the process user can write `storage/logs/worker.log`, `storage/logs/cron.log`, and heartbeat files under `storage/logs/`.
- Run migrations in `deployment/migrations/README.md`.
- Keep `APP_ENV=production`, `APP_DEBUG=false`, and rotate `CRON_TOKEN` to a random secret value.
- Restart the worker after `.env` changes so new queue/mail/runtime settings are applied:
  `sudo systemctl restart market-worker.service`
- Disable or remove the web installer after the first install.
- Run `php tools/ready_check.php` and then `php tools/run-quality-gates.php` before switching traffic.

5. Post-deploy quick verification

- Open `/admin/pulse` and confirm DB, disk, and cron heartbeat are healthy.
- Confirm the queue worker heartbeat is healthy and queue backlog is not growing.
- Trigger one queued action and make sure `storage/logs/worker.log` shows it being processed.
- Trigger one email flow and verify both delivery and the sender identity.
- Run one backup and one restore drill on a staging copy before relying on production backups.
