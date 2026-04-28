# Database Hardening Migrations

## Run

- From project root:
- `php deployment/migrations/20260331_hardening.php`
- `php deployment/migrations/20260414_analytics.php`
- `php deployment/migrations/20260414_product_translations.php`
- `php deployment/migrations/20260414_product_status.php`
- `php deployment/migrations/20260427_webhook_monitoring.php`
- `php deployment/migrations/20260427_data_integrity.php`
- `php deployment/migrations/20260427_registration_analytics.php`
- `php deployment/migrations/20260427_registration_analytics_backfill.php`
- `php deployment/migrations/20260427_product_demo_fields.php`

## What it applies

- Adds missing performance/safety indexes for payment, queue, and wallet tables.
- Adds unique index for `licenses.license_key`.
- Adds `jobs.last_error` for retry/dead-letter diagnostics.
- Adds auth-related `users` columns for password reset and OAuth linking.
- Adds analytics tables for page views, login history, and IP-to-country cache.
- Adds `product_translations` with RU/EN backfill for localized product content.
- Adds `products.status` for draft/published workflows.
- Adds `webhook_failures` table for rejected webhook monitoring and alerting.
- Adds unique constraints for `reviews(user_id, product_id)` and `chat_threads(user_id, product_id)` with duplicate cleanup.
- Adds `analytics_registrations` table for precise source/provider registration tracking.
- Backfills historical registrations from `users` into `analytics_registrations` and enforces one row per user.
- Adds product demo fields (`demo_enabled`, demo URL/login/password) for storefront demo access blocks.

## Notes

- Migration is idempotent: it checks schema metadata before altering tables.
- Ensure `.env` is present and DB credentials are valid before running.
- Validate registration analytics consistency with `php tools/analytics_registration_check.php`.
