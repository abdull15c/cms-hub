# Database Hardening Migrations

## Run

- From project root:
- `php deployment/migrations/20260331_hardening.php`
- `php deployment/migrations/20260414_analytics.php`
- `php deployment/migrations/20260414_product_translations.php`
- `php deployment/migrations/20260414_product_status.php`

## What it applies

- Adds missing performance/safety indexes for payment, queue, and wallet tables.
- Adds unique index for `licenses.license_key`.
- Adds `jobs.last_error` for retry/dead-letter diagnostics.
- Adds auth-related `users` columns for password reset and OAuth linking.
- Adds analytics tables for page views, login history, and IP-to-country cache.
- Adds `product_translations` with RU/EN backfill for localized product content.
- Adds `products.status` for draft/published workflows.

## Notes

- Migration is idempotent: it checks schema metadata before altering tables.
- Ensure `.env` is present and DB credentials are valid before running.
