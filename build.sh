#!/usr/bin/env bash

set -euo pipefail

VERSION="${1:-1.0.0}"
PLUGIN_SLUG="ConjureWP"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_ROOT="${ROOT_DIR}/build-freemius"
BUILD_PLUGIN_DIR="${BUILD_ROOT}/${PLUGIN_SLUG}"
DIST_DIR="${ROOT_DIR}/dist"
OUTPUT_ZIP="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
IGNORE_FILE="${ROOT_DIR}/.freemiusignore"

red='\033[0;31m'
green='\033[0;32m'
yellow='\033[1;33m'
nc='\033[0m'

info() {
  printf "${yellow}%s${nc}\n" "$1"
}

ok() {
  printf "${green}%s${nc}\n" "$1"
}

fail() {
  printf "${red}Error: %s${nc}\n" "$1" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "'$1' is required but not installed."
}

cd "${ROOT_DIR}"

require_cmd rsync
require_cmd zip

if [[ ! -f "${IGNORE_FILE}" ]]; then
  fail "Missing .freemiusignore at ${IGNORE_FILE}"
fi

ok "ConjureWP Freemius build"
printf "Version: %s\n\n" "${VERSION}"

info "Cleaning previous build artefacts..."
rm -rf "${BUILD_ROOT}"
mkdir -p "${BUILD_PLUGIN_DIR}" "${DIST_DIR}"
rm -f "${OUTPUT_ZIP}"

if [[ -f "${ROOT_DIR}/package.json" ]]; then
  require_cmd npm
  info "Building front-end assets..."
  npm run build
fi

info "Copying plugin files..."
rsync -a \
  --exclude-from="${IGNORE_FILE}" \
  --exclude='.git/' \
  --exclude='build-freemius/' \
  --exclude='dist/' \
  --exclude='*.zip' \
  "${ROOT_DIR}/" "${BUILD_PLUGIN_DIR}/"

if [[ -f "${ROOT_DIR}/composer.json" ]]; then
  require_cmd composer
  info "Installing production Composer dependencies..."
  cp "${ROOT_DIR}/composer.json" "${BUILD_PLUGIN_DIR}/"
  if [[ -f "${ROOT_DIR}/composer.lock" ]]; then
    cp "${ROOT_DIR}/composer.lock" "${BUILD_PLUGIN_DIR}/"
  fi

  (
    cd "${BUILD_PLUGIN_DIR}"
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet
    rm -f composer.json composer.lock
  )
fi

info "Creating Freemius ZIP package..."
(
  cd "${BUILD_ROOT}"
  zip -r "${OUTPUT_ZIP}" "${PLUGIN_SLUG}" -q
)

SIZE="$(du -h "${OUTPUT_ZIP}" | awk '{print $1}')"
ok "Build complete"
printf "Output: %s\n" "${OUTPUT_ZIP}"
printf "Size:   %s\n" "${SIZE}"
