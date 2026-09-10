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

### Tray reopen correction

User reported that clicking the tray after closing did not restore a visible window.
The reopen action now unhides the accessory application, unminimizes/shows/focuses the
window, and handles mouse-down (without depending on mouse-up delivery). The rebuilt
package passes the runtime smoke test; manual close/tray-reopen retest is pending.

### Desktop milestone accepted

The user confirmed the rebuilt tray click reopens the closed window. Tray Quit was also
verified by the user and corroborated by FrankenPHP's SIGTERM / exit-code-0 log.

## Second demo — Symfony proxy directory (before Messenger monitor)

Read the Symfony proxy's new index.json, show running applications and their proxy URLs,
and open a selected URL in the default browser. Verify the actual index schema and URL
before implementation. This is the smallest second consumer of the reusable desktop shell;
Messenger queues become the third, richer use case. Include this progression in the
planned Medium article after the implementations work.

### AQI foundation checkpoint

Published airnow-bundle 2.28 installed through Composer. Doctrine SQLite Observation entity,
repository, migration, Settings abstraction and method-level aqi:fetch implemented.
Kernel integration test verifies real bundle-to-monitor persistence and idempotent refresh.
The desktop remains the Hello UI; no claim of a finished AQI dashboard/settings/history UI.
The user is considering the proxy directory as the first finished example before AirNow.

## AQI UI milestone and distribution

Dashboard, settings, history, validated forms, CSRF-protected refresh and Stimulus polling
implemented. RefreshState distinguishes failure, empty response and latest successful
observations without mixing response batches. Desktop startup runs Doctrine migrations
using bundled PHP. Four tests / 41 assertions and packaged resource smoke test pass.
The rebuilt native Air Quality window renders successfully.

The user authorized public publication and requested `survos-sites/airnow`; the repository
has been created and the initial milestones pushed. The GitHub release workflow follows
Tauri's signing support and additionally notarizes/verifies the DMG. Apple account recovery
and membership verification are in progress; no official signed release exists yet.
Display name is Air Quality; identifier stays com.survos.airnow for persisted data continuity.

The proxy site monitor can add scheduled HTTP smoke checks against Symfony CLI-served apps.
The Scheduler consumer would hold monitor code, not the checked applications' code; dev
reload is owned by those Symfony CLI servers. Keep this enhancement for the next demo.

## Hosted AirNow service proposal

For a general audience, default to a hosted Symfony observation/forecast API using
survos/airnow-bundle with shared per-location caching. Keep direct AirNow + personal key
as an advanced option, behind a small observation-source interface in the desktop app.
A desktop refresh must not bypass a public service's shared cache. Validate ZIPs and
rate-limit the hosted service. This is a proposal, not an implemented or deployed service;
no domain has been selected. Read AirNow's FAQ/data-exchange guidelines before release,
including source attribution, preliminary-data labeling and informing the relevant agencies.

Local DMG creation was blocked by hdiutil “Device not configured”; unsigned DMG creation
and mount/runtime checks are running on both GitHub macOS runners instead. Current
uncompressed Apple Silicon app size: 213 MB. Runtime and shell both declare macOS 12 minimum.
