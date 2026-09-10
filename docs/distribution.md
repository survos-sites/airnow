# macOS distribution

The app uses **Tauri 2 → FrankenPHP → PHP 8.5 / Symfony 8.1**. We retain the stable
bundle identifier `com.survos.airnow` so installed preferences survive updates.
The display name is **Air Quality**. This is direct distribution, without Mac App
Sandbox or the Mac App Store. The earlier NativePHP/Electron release draft does
not describe this implementation.

## Local builds

Follow the README prerequisites, then run:

```bash
npm run desktop:build
npx tauri build --bundles app,dmg
bash scripts/verify-dmg.sh src-tauri/target/release/bundle/dmg/*.dmg --local
```

Local builds require no Apple credentials. They are unsigned/ad-hoc development
artifacts and do not provide trusted Internet distribution. CI also builds and
mount-tests these local artifacts on both architectures without signing credentials. Never advertise a local
DMG as notarized. The app runs migrations using its own bundled PHP on launch.

## Official builds

`.github/workflows/release.yml` builds from a clean tagged checkout on separate
Apple Silicon and Intel runners. Separate packages keep downloads smaller and avoid
combining the architecture-specific FrankenPHP executables into a custom Universal
runtime. Both architectures must pass before a GitHub Release is published.

`package.json` supplies the local application version to Tauri. For a release,
`scripts/release-version.mjs` derives it from the tag and synchronizes npm and Rust
package metadata in the disposable CI checkout.

1. Build and test Symfony and the native shell.
2. Tauri signs the app, including its bundled runtime, with Developer ID Application,
   enables its default hardened runtime, and notarizes/staples the app.
3. Sign and submit the DMG using Apple's `notarytool`; require `Accepted`, then staple.
4. Verify signatures, stapled tickets and Gatekeeper assessments. Mount the DMG read-only,
   check `Air Quality.app` and the `/Applications` shortcut, and run the packaged Symfony
   smoke test from that mounted image with writable data outside the app.
5. Publish versioned DMGs and SHA-256 files to this repository's GitHub Releases.

There is no unsigned fallback for official tags. Missing credentials or failed Apple
verification fail the build. The workflow has not been verified against Apple's services
until a real signed release completes; local tests alone cannot establish notarization.

## Apple prerequisites and GitHub secrets

First sign in at https://developer.apple.com/account/ and check Membership details:
team, role and renewal status. An old research app may belong to an institution's team.
An Apple Account alone is not a paid Developer Program membership. You need an active
eligible membership and access to a **Developer ID Application** certificate with its
private key. An old iOS/App Store distribution certificate is not the same identity.

Set these repository Actions secrets under Settings → Secrets and variables → Actions:

| Secret | Value |
| --- | --- |
| `APPLE_CERTIFICATE_BASE64` | Base64 of an exported password-protected `.p12`, including the Developer ID Application private key |
| `APPLE_CERTIFICATE_PASSWORD` | Password used when exporting that `.p12` |
| `APPLE_SIGNING_IDENTITY` | Full `Developer ID Application: … (TEAMID)` identity |
| `APPLE_API_KEY_ID` | App Store Connect **team API key** ID (mapped to Tauri's `APPLE_API_KEY`) |
| `APPLE_API_ISSUER` | Issuer UUID for that team API key |
| `APPLE_API_PRIVATE_KEY` | Full downloaded `.p8` text, including its header/footer |

Create the team API key in App Store Connect → Users and Access → Integrations with
appropriate developer access. The workflow writes it to a temporary file and supplies
`APPLE_API_KEY_PATH`; it never uses your Apple Account password. Individual API keys
without an issuer are not supported by this workflow. Signing files and a temporary
keychain are deleted even if the job fails. No AirNow API key belongs in release secrets:
each installed app gets its own key through Settings.

Find installed signing identities with `security find-identity -v -p codesigning`.
Export the certificate plus private key from Keychain Access as `.p12`. Convert outside
the repository with `openssl base64 -A -in certificate.p12 -out certificate-base64.txt`.
Paste the result into the named GitHub secret; do not paste credentials into chat or commits.

## Releasing

After CI is green and the signing secrets are configured:

```bash
git tag v0.2.0
git push origin v0.2.0
```

Expected assets: `Air-Quality-0.2.0-arm64.dmg`, `Air-Quality-0.2.0-x86_64.dmg`,
and matching checksum files. Prerelease tags such as `v0.2.0-beta.1` create prereleases.
Use the Releases page to select the right architecture; no ambiguous stable filename alias.
The final publish job runs only after both architecture builds and Apple checks succeed.

Download through a browser, drag the app into Applications, and launch normally. macOS
may still show its standard first-launch Internet-download confirmation; notarization
avoids the unidentified-developer/unverified-app failure, not every OS confirmation.

```bash
codesign --verify --deep --strict --verbose=2 '/Applications/Air Quality.app'
spctl --assess --type execute --verbose=2 '/Applications/Air Quality.app'
xcrun stapler validate '/Applications/Air Quality.app'
```

Check setup, AQI refresh, history, close/reopen from the tray, and Quit. Check startup
with an existing database as well as a fresh installation. Keep signing validation on
the final artifact; modifying anything in `.app` afterward invalidates its signature.

## Later: updates and article

Tauri has a standard updater plugin, GitHub-compatible endpoints, and signed updater
artifacts. It needs a separate updater signing key and update UI/lifecycle handling;
Developer ID signing does not replace that signature. This milestone deliberately ends
at reliable Releases. Preserve the identifier and add the standard plugin when ready.

The Medium article will explain the ordinary Symfony app, small native shell, process
lifecycle, writable data paths, measured package size, and release workflow. Credit
https://github.com/breadthe/aqi-desktop as the functional inspiration. The proxy-site
monitor and eventual Messenger queue monitor are follow-up examples.

References: [Tauri signing](https://v2.tauri.app/distribute/sign/macos/),
[Tauri DMG](https://v2.tauri.app/distribute/dmg/),
[Tauri updater](https://v2.tauri.app/plugin/updater/),
[Apple Developer ID](https://developer.apple.com/help/account/certificates/create-developer-id-certificates).
