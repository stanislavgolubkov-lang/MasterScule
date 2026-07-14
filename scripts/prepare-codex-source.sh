#!/usr/bin/env bash
set -euo pipefail

SOURCE="$(pwd)"
TARGET="${1:-../MasterScule-codex-source}"

if [[ "$TARGET" != /* ]]; then
  TARGET="$SOURCE/$TARGET"
fi

SOURCE_REAL="$(cd "$SOURCE" && pwd -P)"
TARGET_PARENT="$(dirname "$TARGET")"
mkdir -p "$TARGET_PARENT"
TARGET_REAL_PARENT="$(cd "$TARGET_PARENT" && pwd -P)"
TARGET_REAL="$TARGET_REAL_PARENT/$(basename "$TARGET")"

if [[ "$TARGET_REAL" == "/" || "$TARGET_REAL" == "${HOME:-__no_home__}" ]]; then
  echo "Refusing unsafe target: $TARGET_REAL" >&2
  exit 1
fi

if [[ "$TARGET_REAL" == "$SOURCE_REAL" || "$TARGET_REAL" == "$SOURCE_REAL/"* || "$SOURCE_REAL" == "$TARGET_REAL/"* ]]; then
  echo "Refusing overlapping source and target paths: $TARGET_REAL" >&2
  exit 1
fi

rm -rf -- "$TARGET_REAL"
mkdir -p "$TARGET_REAL"

rsync -a "$SOURCE_REAL/" "$TARGET_REAL/" \
  --exclude vendor \
  --exclude node_modules \
  --exclude .git \
  --exclude .env \
  --exclude '.phpunit.result.cache' \
  --exclude 'database/*.sqlite' \
  --exclude 'database/*.db' \
  --exclude storage/app/public \
  --exclude storage/app/private \
  --exclude storage/logs \
  --exclude storage/framework/cache \
  --exclude storage/framework/sessions \
  --exclude storage/framework/views \
  --exclude public/storage \
  --exclude public/build \
  --exclude repo-meta \
  --exclude masterscule \
  --exclude masterscule-git \
  --exclude '*.zip' \
  --exclude '*.tar' \
  --exclude '*.gz'

echo "Clean source copy created at: $TARGET_REAL"
