#!/bin/bash
set -euo pipefail
# --local verifies layout only. Official releases must pass Apple verification too.
dmg="$1"
mode="${2:-release}"
mount_point=$(mktemp -d)
cleanup() { hdiutil detach "$mount_point" -quiet || true; rmdir "$mount_point" || true; }
trap cleanup EXIT
if [[ "$mode" != --local ]]; then
    codesign --verify --strict --verbose=2 "$dmg"
    xcrun stapler validate "$dmg"
    spctl --assess --type open --context context:primary-signature --verbose=2 "$dmg"
fi
hdiutil attach "$dmg" -nobrowse -readonly -mountpoint "$mount_point" -quiet
test -d "$mount_point/Air Quality.app"
test "$(readlink "$mount_point/Applications")" = /Applications
if [[ "$mode" != --local ]]; then
    codesign --verify --deep --strict --verbose=2 "$mount_point/Air Quality.app"
    codesign --verify --strict --verbose=2 "$mount_point/Air Quality.app/Contents/MacOS/frankenphp"
    xcrun stapler validate "$mount_point/Air Quality.app"
    spctl --assess --type execute --verbose=2 "$mount_point/Air Quality.app"
fi
node scripts/check-package.mjs "$mount_point/Air Quality.app"
