import { readFileSync, writeFileSync } from 'node:fs';
const tag = process.argv[2];
if (!/^v\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/.test(tag ?? '')) throw new Error('Expected a release tag such as v0.2.0');
const version = tag.slice(1);
for (const file of ['package.json', 'package-lock.json']) {
    const data = JSON.parse(readFileSync(file));
    data.version = version;
    if (data.packages?.['']) data.packages[''].version = version;
    writeFileSync(file, JSON.stringify(data, null, 2) + '\n');
}
for (const file of ['src-tauri/Cargo.toml', 'src-tauri/Cargo.lock']) {
    const text = readFileSync(file, 'utf8');
    writeFileSync(file, text.replace(/(name = "airnow-desktop"\nversion = ")[^"]+/, `$1${version}`));
}
console.log(`Release version: ${version}`);
