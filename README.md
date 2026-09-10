# AirNow — Symfony Desktop

An ordinary PHP 8.5 / Symfony 8.1 application inside a small Tauri 2 macOS shell.
FrankenPHP provides the bundled PHP/web-server runtime. Twig provides the UI.

Inspired by [breadthe/aqi-desktop](https://github.com/breadthe/aqi-desktop), credited for
the menu-bar AQI application concept and functional reference. This is an independent
Symfony implementation, not a conversion of its Laravel/NativePHP application.

## Development

Requires PHP 8.5, Composer, Node/npm, Rust via rustup, and Xcode Command Line Tools.
Rust and Node are build tools; users of the packaged app need none of these tools.

```bash
composer install
npm ci
npm run runtime:download
npm run desktop:dev
```

`runtime:download` retrieves FrankenPHP **1.12.7**, verifies its pinned SHA-256 digest,
and names it for Tauri's host target. Apple Silicon and Intel download targets are
provided; only the host architecture is built. Commit both dependency lockfiles.

For ordinary Symfony browser development:

```bash
symfony server:start
# Or run the actual bundled runtime (choose the binary matching your Mac):
DESKTOP_PORT=8765 src-tauri/binaries/frankenphp-aarch64-apple-darwin run --config desktop/Caddyfile
```

## Build

```bash
npm run desktop:build
open src-tauri/target/release/bundle/macos/AirNow.app
```

The build stages only application source/configuration and production Composer dependencies
into `desktop/app`, then bundles them as Tauri resources. `.env.local`, development caches,
and user data are excluded. Build commands require Composer/network access when dependencies
are not cached. The release executable uses resources inside its own `.app`, not the checkout.

This produces a local unsigned/ad-hoc macOS app. Distribution signing and notarization need
an Apple Developer identity and are a separate release step. No release has been published.

## Architecture and lifecycle

- Tauri starts the FrankenPHP sidecar on an available IPv4 loopback port.
- Caddy automatic HTTPS, admin endpoint and configuration persistence are disabled.
- The shell waits for the Symfony health route, then opens a 400×510 WebView window.
- Left-click the tray icon to open; right-click for Open/Quit. Closing the window hides it.
- Explicit Quit sends SIGTERM to FrankenPHP, waits up to five seconds, then kills/reaps it
  if necessary. Startup failure also disposes of the child.
- A fresh desktop token is exchanged for an HttpOnly, SameSite cookie. Unauthenticated
  dynamic requests are rejected; external WebView navigation is disabled. No native IPC
  permissions are granted to the rendered application.
- In desktop development, writable data is `var/desktop`. Packaged apps use
  `~/Library/Application Support/com.survos.airnow`. Runtime diagnostics are in `runtime.log`.
- Symfony's Kernel redirects cache/log paths through `APP_DATA_DIR`. No runtime writes belong
  in the signed/read-only `.app`. Plain browser development defaults to the project's `var`.

The native code is a shell around an ordinary web app. Application services never call Tauri.
We use standard FrankenPHP request mode initially. Worker mode and embedded-PHP compilation
are unnecessary for the proof and can be measured later.

FrankenPHP also supports [embedding PHP applications in a rebuilt executable](https://frankenphp.dev/docs/embed/).
For this experiment the [self-contained runtime](https://frankenphp.dev/docs/) plus
[Tauri sidecar](https://v2.tauri.app/develop/sidecar/) and resource files gives a simpler
build pipeline and keeps Symfony deployment conventional.

## Verification

```bash
php bin/phpunit
php bin/console lint:container
php bin/console lint:twig templates
cargo check --manifest-path src-tauri/Cargo.toml
```

Manual native acceptance: launch, verify the page, close the window, reopen from the tray,
quit from the tray menu, and verify no child/listening port remains. Test the packaged `.app`
from outside the source checkout as well as development mode.

## Roadmap

See PLAN.md: first prove the desktop shell, then add the published `survos/airnow-bundle`,
Doctrine SQLite history, settings, and Stimulus interaction. A Messenger queue monitor is the
next practical consumer. A Medium article will document the working build with measurements,
screenshots and credit to the original AQI repository.

Automated packaged-runtime smoke test (after building):

```bash
node scripts/check-package.mjs
```

It boots FrankenPHP and Symfony from the `.app` resources, checks desktop authentication
and the rendered page, requests SIGTERM, asserts exit code 0, and confirms the port closes.
Production caches are separated by a fingerprint of staged source/config/dependencies.

Initial measurement on Apple Silicon: approximately 193 MB uncompressed for the Hello app,
mostly the general-purpose FrankenPHP binary (169 MB). A custom extension-minimal runtime
could reduce this later; the first build prioritizes the supported prebuilt runtime.
