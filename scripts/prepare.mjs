import { createHash } from 'node:crypto';
import { cpSync, mkdirSync, rmSync, writeFileSync, readFileSync, readdirSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
const dev = process.argv.includes('--dev');
mkdirSync('desktop/app', { recursive: true });
if (!dev) {
    rmSync('desktop/app', { recursive: true, force: true });
    mkdirSync('desktop/app', { recursive: true });
    for (const path of ['bin', 'config', 'public', 'src', 'templates', 'composer.json', 'composer.lock', 'symfony.lock']) {
        cpSync(path, `desktop/app/${path}`, { recursive: true });
    }
    const hash = createHash('sha256');
    function fingerprint(path) {
        for (const item of readdirSync(path, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name))) {
            const file = `${path}/${item.name}`;
            if (item.isDirectory()) fingerprint(file);
            else { hash.update(file); hash.update(readFileSync(file)); }
        }
    }
    fingerprint('desktop/app');
    writeFileSync('desktop/app/.build-id', hash.digest('hex').slice(0, 16));
    // Explicit clean defaults. Never copy .env.local, dev cache, credentials or user data.
    writeFileSync('desktop/app/.env', 'APP_ENV=prod\nAPP_DEBUG=0\nAPP_SECRET=\n');
    execFileSync('composer', ['install', '--working-dir=desktop/app', '--no-dev', '--no-scripts', '--prefer-dist', '--no-interaction', '--optimize-autoloader'], { stdio: 'inherit' });
}
