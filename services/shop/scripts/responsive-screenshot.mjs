import { spawn } from 'node:child_process';
import { existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';

const url = process.argv[2] ?? 'http://127.0.0.1:18080';
const chrome = process.env.CHROME_PATH ?? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
if (! existsSync(chrome)) throw new Error(`Chrome not found: ${chrome}`);

const profile = mkdtempSync(join(tmpdir(), 'hugo-shop-chrome-'));
const output = mkdtempSync(join(tmpdir(), 'hugo-shop-responsive-'));
const browser = spawn(chrome, [
    '--headless=new',
    '--disable-gpu',
    '--disable-extensions',
    '--no-first-run',
    '--remote-debugging-port=0',
    `--user-data-dir=${profile}`,
    'about:blank',
], { windowsHide: true, stdio: 'ignore' });

const wait = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
let port;
for (let attempt = 0; attempt < 50; attempt++) {
    const portFile = join(profile, 'DevToolsActivePort');
    if (existsSync(portFile)) {
        port = Number.parseInt(readFileSync(portFile, 'utf8').split(/\r?\n/)[0], 10);
        break;
    }
    await wait(100);
}
if (! port) throw new Error('Chrome DevTools port was not created');

const target = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent(url)}`, { method: 'PUT' }).then((response) => response.json());
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
});

let messageId = 0;
const pending = new Map();
socket.addEventListener('message', (event) => {
    const message = JSON.parse(event.data);
    if (! message.id || ! pending.has(message.id)) return;
    const { resolve, reject } = pending.get(message.id);
    pending.delete(message.id);
    if (message.error) reject(new Error(message.error.message));
    else resolve(message.result);
});

function call(method, params = {}) {
    const id = ++messageId;
    socket.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => pending.set(id, { resolve, reject }));
}

const results = [];
try {
    await call('Page.enable');
    await call('Runtime.enable');
    const viewports = [
        { name: 'desktop-1280', width: 1280, height: 800 },
        { name: 'mobile-320', width: 320, height: 720 },
        { name: 'mobile-375', width: 375, height: 812 },
        { name: 'mobile-414', width: 414, height: 896 },
        { name: 'tablet-768', width: 768, height: 1024 },
    ];
    for (const viewport of viewports) {
        await call('Emulation.setDeviceMetricsOverride', {
            width: viewport.width,
            height: viewport.height,
            deviceScaleFactor: 1,
            mobile: viewport.width < 768,
            screenWidth: viewport.width,
            screenHeight: viewport.height,
        });
        await call('Page.navigate', { url });
        for (let attempt = 0; attempt < 50; attempt++) {
            const state = await call('Runtime.evaluate', { expression: 'document.readyState', returnByValue: true });
            if (state.result.value === 'complete') break;
            await wait(100);
        }
        await call('Runtime.evaluate', { expression: 'document.fonts.ready', awaitPromise: true });
        await wait(200);
        const metrics = await call('Runtime.evaluate', {
            expression: `({
                innerWidth,
                innerHeight,
                scrollWidth: document.documentElement.scrollWidth,
                bodyScrollWidth: document.body.scrollWidth,
                title: document.title
            })`,
            returnByValue: true,
        });
        const screenshot = await call('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false, fromSurface: true });
        const path = join(output, `${viewport.name}.png`);
        writeFileSync(path, Buffer.from(screenshot.data, 'base64'));
        results.push({ ...viewport, ...metrics.result.value, path });
    }
    console.log(JSON.stringify(results));
} finally {
    await call('Browser.close').catch(() => {});
    socket.close();
    browser.kill();
    const resolvedProfile = resolve(profile);
    if (dirname(resolvedProfile) !== resolve(tmpdir()) || ! basename(resolvedProfile).startsWith('hugo-shop-chrome-')) {
        throw new Error(`Refusing to remove unexpected profile path: ${resolvedProfile}`);
    }
    for (let attempt = 0; attempt < 20; attempt++) {
        try {
            rmSync(profile, { recursive: true, force: true });
            break;
        } catch (error) {
            if (error.code !== 'EPERM' || attempt === 19) throw error;
            await wait(100);
        }
    }
}
