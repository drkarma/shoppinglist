#!/usr/bin/env bash
set -euo pipefail

# Root på appen = där detta script ligger
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

BACKUP_DIR="$APP_DIR/backups"
mkdir -p "$BACKUP_DIR"

# Hitta nästa versionsnummer
latest=$(ls -1 "$BACKUP_DIR"/shopylist--backup-v*.zip 2>/dev/null \
  | sed -E 's/.*-v([0-9]+)\.zip/\1/' \
  | sort -n | tail -n1 || true)

if [[ -z "${latest:-}" ]]; then
  next=1
else
  next=$((latest + 1))
fi

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/shopylist--backup-v${next}.zip"
MANIFEST=".backup_manifest_${STAMP}.txt"

# Skapa manifest (hamnar med i zip-filen)
{
  echo "Backup of: $APP_DIR"
  echo "Created : $(date -u) UTC"
  echo "Version : v$next"
  echo "Host    : $(hostname)"
  echo "User    : $(whoami)"
  echo
  echo "File list (excluding backups/ and .git/):"
  find . -path ./backups -prune -o -path ./.git -prune -o -print | sed 's|^\./||'
} > "$MANIFEST"

# Skapa zip (inkludera allt utom backups/ och .git/)
if command -v zip >/dev/null 2>&1; then
  zip -rq "$OUT" . \
    -x "backups/*" \
    -x ".git/*" \
    -x "*.DS_Store" "*.tmp" "*.swp"
else
  # Fallback om zip inte finns (använd tar.gz)
  OUT="${OUT%.zip}.tar.gz"
  tar --exclude='./backups' --exclude='./.git' --exclude='*.DS_Store' --exclude='*.tmp' --exclude='*.swp' -czf "$OUT" .
fi

# Städa manifest (är redan med i zip/tar eftersom det skapades före paketeringen)
rm -f "$MANIFEST"

# Praktisk symlink till senaste backup
ln -sfn "$OUT" "$BACKUP_DIR/latest.zip"

echo "✅ Backup skapad: $OUT"
echo "🔗 Senaste backup pekar på: $BACKUP_DIR/latest.zip"
