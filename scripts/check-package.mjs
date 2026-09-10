import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { mkdirSync, mkdtempSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import net from 'node:net';
import assert from 'node:assert/strict';
const bundle = resolve(process.argv[2] ?? 'src-tauri/target/release/bundle/macos/AirNow.app');
const resources = `${bundle}/Contents/Resources`;
mkdirSync('var', { recursive: true });
const data = mkdtempSync(resolve('var/package-check-'));
const listener = net.createServer();
listener.listen(0, '127.0.0.1'); await once(listener, 'listening');
const port = listener.address().port;
await new Promise(resolve => listener.close(resolve));
const url = `http://127.0.0.1:${port}`;
const child = spawn(`${bundle}/Contents/MacOS/frankenphp`, ['run', '--config', `${resources}/Caddyfile`], {
    cwd: `${resources}/app`,
    env: { ...process.env, APP_ENV: 'prod', APP_DEBUG: '0', APP_DATA_DIR: data,
        APP_BUILD_ID: readFileSync(`${resources}/app/.build-id`, 'utf8'),
        APP_SECRET: 'package-test', DESKTOP_TOKEN: 'package-test', DEFAULT_URI: url,
        DESKTOP_PORT: `${port}`, APP_PUBLIC_DIR: `${resources}/app/public` },
    stdio: ['ignore', 'ignore', 'pipe'],
});
let log = ''; child.stderr.on('data', chunk => { log += chunk; });
const exit = once(child, 'exit');
try {
    let ready = false;
    for (let i = 0; i < 100; i++) {
        try { ready = (await (await fetch(`${url}/health`)).text()) === 'symfony-desktop-ready'; } catch {}
        if (ready) break;
        if (child.exitCode !== null) throw new Error(`Runtime exited: ${log}`);
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    assert(ready, `Packaged Symfony failed to start: ${log}`);
    assert.equal((await fetch(url)).status, 403);
    const bootstrap = await fetch(`${url}/?desktop_token=package-test`, { redirect: 'manual' });
    assert.equal(bootstrap.status, 302);
    const cookie = bootstrap.headers.get('set-cookie').split(';')[0];
    const page = await fetch(url, { headers: { Cookie: cookie } });
    assert.equal(page.status, 200);
    assert((await page.text()).includes('Hello from Symfony Desktop'));
} finally {
    child.kill('SIGTERM');
    const fallback = setTimeout(() => child.kill('SIGKILL'), 6000);
    await exit;
    clearTimeout(fallback);
}
assert.equal(child.exitCode, 0, log);
await assert.rejects(fetch(`${url}/health`));
console.log('Packaged PHP/Symfony, desktop authentication and graceful shutdown verified.');
