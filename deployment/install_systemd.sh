#!/usr/bin/env bash
set -euo pipefail

usage() {
  echo "Usage: sudo bash deployment/install_systemd.sh /var/www/market /usr/bin/php www-data [group]"
}

if [[ $# -lt 3 || $# -gt 4 ]]; then
  usage
  exit 1
fi

if [[ ${EUID:-0} -ne 0 ]]; then
  echo "Run this script as root (for example: sudo bash deployment/install_systemd.sh ...)." >&2
  exit 1
fi

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
APP_ROOT="$(CDPATH= cd -- "$1" && pwd)"
PHP_BIN="$2"
APP_USER="$3"
APP_GROUP="${4:-$APP_USER}"

for file in "$SCRIPT_DIR/market-worker.service" "$SCRIPT_DIR/market-cron.service" "$SCRIPT_DIR/market-cron.timer"; do
  if [[ ! -f "$file" ]]; then
    echo "Missing template: $file" >&2
    exit 1
  fi
done

install -d "$APP_ROOT/storage/logs"
touch "$APP_ROOT/storage/logs/worker.log" "$APP_ROOT/storage/logs/cron.log"
chown "$APP_USER:$APP_GROUP" "$APP_ROOT/storage/logs/worker.log" "$APP_ROOT/storage/logs/cron.log"
chmod 664 "$APP_ROOT/storage/logs/worker.log" "$APP_ROOT/storage/logs/cron.log"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

render_unit() {
  local source_file="$1"
  local target_file="$2"
  sed \
    -e "s|{{APP_ROOT}}|$APP_ROOT|g" \
    -e "s|{{PHP_BIN}}|$PHP_BIN|g" \
    -e "s|{{APP_USER}}|$APP_USER|g" \
    -e "s|{{APP_GROUP}}|$APP_GROUP|g" \
    "$source_file" > "$target_file"
}

render_unit "$SCRIPT_DIR/market-worker.service" "$TMP_DIR/market-worker.service"
render_unit "$SCRIPT_DIR/market-cron.service" "$TMP_DIR/market-cron.service"
render_unit "$SCRIPT_DIR/market-cron.timer" "$TMP_DIR/market-cron.timer"

install -m 644 "$TMP_DIR/market-worker.service" /etc/systemd/system/market-worker.service
install -m 644 "$TMP_DIR/market-cron.service" /etc/systemd/system/market-cron.service
install -m 644 "$TMP_DIR/market-cron.timer" /etc/systemd/system/market-cron.timer

systemctl daemon-reload
systemctl enable --now market-worker.service market-cron.timer
systemctl start market-cron.service

echo "Systemd units installed."
echo "Check status:"
echo "  sudo systemctl status market-worker.service"
echo "  sudo systemctl status market-cron.timer"
