import { mkdirSync, writeFileSync, chmodSync } from 'node:fs';
import { createHash } from 'node:crypto';
const targets = {
    arm64: ['arm64', 'aarch64-apple-darwin', 'b37871675c86171234b25ba0ed47232206e0840d57f730bf564c464722318cd6'],
    x64: ['x86_64', 'x86_64-apple-darwin', '69859a0fecc3f6f33d82eefac1f91c165ed39224e8566d3b4f45710f3fee4963'],
};
if (process.platform !== 'darwin' || !targets[process.arch]) throw new Error('This proof of concept targets macOS.');
const [arch, triple, digest] = targets[process.arch];
const response = await fetch(`https://github.com/php/frankenphp/releases/download/v1.12.7/frankenphp-mac-${arch}`);
if (!response.ok) throw new Error(`Runtime download failed: ${response.status}`);
const binary = Buffer.from(await response.arrayBuffer());
if (createHash('sha256').update(binary).digest('hex') !== digest) throw new Error('Runtime checksum mismatch.');
mkdirSync('src-tauri/binaries', { recursive: true });
const path = `src-tauri/binaries/frankenphp-${triple}`;
writeFileSync(path, binary); chmodSync(path, 0o755);
console.log(`Downloaded and verified FrankenPHP 1.12.7: ${path}`);
