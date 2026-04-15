#!/usr/bin/env bash
set -euo pipefail

usage() {
  echo "Usage: sudo bash deployment/install_worker.sh /var/www/market /usr/bin/php www-data [group]"
}

if [[ $# -lt 3 || $# -gt 4 ]]; then
  usage
  exit 1
fi

if [[ ${EUID:-0} -ne 0 ]]; then
  echo "Run this script as root (for example: sudo bash deployment/install_worker.sh ...)." >&2
  exit 1
fi

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
APP_ROOT="$(CDPATH= cd -- "$1" && pwd)"
PHP_BIN="$2"
APP_USER="$3"
APP_GROUP="${4:-$APP_USER}"
TEMPLATE="$SCRIPT_DIR/cms-hub-worker.conf"
TARGET="/etc/supervisor/conf.d/market-worker.conf"

if [[ ! -f "$TEMPLATE" ]]; then
  echo "Missing template: $TEMPLATE" >&2
  exit 1
fi

if ! command -v supervisorctl >/dev/null 2>&1; then
  echo "Installing Supervisor..."
  apt-get update
  apt-get install -y supervisor
fi

install -d "$APP_ROOT/storage/logs"
touch "$APP_ROOT/storage/logs/worker.log"
chown "$APP_USER:$APP_GROUP" "$APP_ROOT/storage/logs/worker.log"
chmod 664 "$APP_ROOT/storage/logs/worker.log"

sed \
  -e "s|{{APP_ROOT}}|$APP_ROOT|g" \
  -e "s|{{PHP_BIN}}|$PHP_BIN|g" \
  -e "s|{{APP_USER}}|$APP_USER|g" \
  "$TEMPLATE" > "$TARGET"

supervisorctl reread
supervisorctl update
supervisorctl restart market-worker || supervisorctl start market-worker

echo "Worker is running under Supervisor."
echo "Check status: sudo supervisorctl status market-worker"
