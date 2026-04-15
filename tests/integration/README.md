# Integration Tests

## Webhook Security Integration

Runs real DB-backed scenarios for crypto webhook:
- invalid signature is rejected;
- valid signature marks transaction as paid and applies deposit once;
- replay of same signed payload is rejected with no duplicate balance effect.

Also includes auth scenarios:
- password reset token creation and consumption;
- social account creation/linking;
- one-time OAuth state verification.

## Run

Use Laragon PHP (or any PHP with MySQL PDO enabled):

- `php tests/integration/run_all.php`
- `php tests/integration/webhook_integration.php`
- `php tests/integration/auth_integration.php`

Optional env overrides for isolated DB access:
- `TEST_DB_HOST`
- `TEST_DB_PORT`
- `TEST_DB_USER`
- `TEST_DB_PASS`
- `TEST_DB_CHARSET`
- `TEST_CRYPTO_WEBHOOK_SECRET`

The test creates and drops a temporary DB automatically.

If MySQL is unavailable, the suite exits with a clear `[INT-SKIP]` message instead of a fatal PDO error.
