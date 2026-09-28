import { readFileSync } from 'node:fs';

const tokensCss = readFileSync(new URL('../../../tokens.css', import.meta.url), 'utf8');
const appCss = readFileSync(new URL('../resources/css/app.css', import.meta.url), 'utf8');
const tokens = new Map(
    [...tokensCss.matchAll(/--color-([\w-]+):\s*oklch\(([^)]+)\)/g)].map((match) => [match[1], match[2]]),
);

function luminance(value) {
    const [lightness, chroma, hue] = value.trim().split(/\s+/);
    const l = Number.parseFloat(lightness) / (lightness.endsWith('%') ? 100 : 1);
    const c = Number.parseFloat(chroma);
    const radians = Number.parseFloat(hue) * Math.PI / 180;
    const a = c * Math.cos(radians);
    const b = c * Math.sin(radians);
    const lCube = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
    const mCube = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
    const sCube = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;
    const red = Math.min(1, Math.max(0, 4.0767416621 * lCube - 3.3077115913 * mCube + 0.2309699292 * sCube));
    const green = Math.min(1, Math.max(0, -1.2684380046 * lCube + 2.6097574011 * mCube - 0.3413193965 * sCube));
    const blue = Math.min(1, Math.max(0, -0.0041960863 * lCube - 0.7034186147 * mCube + 1.707614701 * sCube));

    return 0.2126 * red + 0.7152 * green + 0.0722 * blue;
}

function ratio(foreground, background) {
    const first = luminance(tokens.get(foreground));
    const second = luminance(tokens.get(background));
    return (Math.max(first, second) + 0.05) / (Math.min(first, second) + 0.05);
}

const pairs = [
    ['ink', 'paper', 4.5],
    ['ink-2', 'paper', 4.5],
    ['muted', 'paper', 4.5],
    ['muted', 'paper-2', 4.5],
    ['accent', 'paper', 4.5],
    ['focus', 'paper', 3],
    ['accent-ink', 'accent', 4.5],
    ['paper', 'ink', 4.5],
    ['paper', 'error', 4.5],
    ['paper', 'success', 4.5],
    ['error', 'paper', 4.5],
    ['ink', 'error-soft', 4.5],
    ['ink', 'accent-soft', 4.5],
    ['warning', 'paper', 3],
];

const failures = [];
for (const [foreground, background, minimum] of pairs) {
    const actual = ratio(foreground, background);
    console.log(`${foreground} on ${background}: ${actual.toFixed(2)}:1`);
    if (actual < minimum) failures.push(`${foreground} on ${background} is below ${minimum}:1`);
}

if (!/html, body\s*\{[^}]*overflow-x:\s*clip/s.test(appCss)) failures.push('page-edge clipping is missing');
if (/transition(?:-property)?:\s*all/.test(appCss)) failures.push('transition-all is present');
if (/(?:#[0-9a-f]{3,8}|oklch\(|rgba?\(|hsla?\()/i.test(appCss)) failures.push('raw colour found outside tokens.css');
for (const declaration of appCss.matchAll(/font-family:\s*([^;]+);/g)) {
    if (!declaration[1].includes('var(--font-')) failures.push(`raw font declaration: ${declaration[1]}`);
}

if (failures.length) {
    console.error(failures.join('\n'));
    process.exit(1);
}

console.log('Hallmark static gates: pass');
