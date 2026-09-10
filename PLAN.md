# AirNow desktop

## Milestone 1 — desktop architecture

PHP 8.5 / Symfony 8.1, Twig, self-contained FrankenPHP macOS sidecar and Tauri 2.
Verify the Hello route, tray open, close-to-hide, explicit quit and child termination,
then commit a runnable milestone. Bundle ordinary Symfony files as Tauri resources;
keep cache, SQLite and settings outside the read-only application bundle.

## Milestone 2 — AirNow

Install published survos/airnow-bundle. Add AqiMonitor, Doctrine/SQLite history,
ZIP/API-key preferences, dashboard, history, settings and Stimulus polling.
Inspect the original reference's views and native behavior before implementation.

## Next application — Messenger queue monitor

A useful second consumer of the same desktop shell: queue counts, failed messages,
worker health and eventually notifications. Decide whether it monitors local transports
or an authenticated remote Symfony endpoint before implementation. Native Rust code
should remain independent of AirNow. Do not extract a desktop Symfony bundle yet.

## Medium article, after it works

Credit https://github.com/breadthe/aqi-desktop as the functional inspiration.
Document the working architecture, why this is not a Laravel conversion, runtime
packaging, tray lifecycle, clean shutdown, writable data paths, development/build
commands, measured app size/startup and limitations. Use screenshots from the working
build. Introduce Messenger monitoring as the practical second application.
Do not publish without the user's instruction.

## Verification recorded

- PHP 8.5.10 / Symfony 8.1.6, Tauri 2.11.5, FrankenPHP 1.12.7.
- PHP HTTP/auth tests and container/Twig lints pass.
- Rust check and macOS release `.app` build pass.
- Actual packaged WebView shows “Hello from Symfony Desktop”.
- Window close leaves the health route responding; normal menu Quit cleanly stops the runtime.
- Automated packaged-resource smoke test passes including authentication and SIGTERM exit 0.
- Tray icon interactions await the user's visual check (native AX tooling omits tray controls).
