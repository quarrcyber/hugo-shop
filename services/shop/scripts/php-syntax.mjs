import fs from 'node:fs';
import path from 'node:path';
import parserPackage from 'php-parser';

const { Engine } = parserPackage;
const parser = new Engine({ parser: { php7: true, suppressErrors: false }, ast: { withPositions: true } });
const roots = ['app', 'bootstrap', 'config', 'database', 'routes', 'tests', '../payment/public', '../mock-shipping/public'];
const failures = [];
let checked = 0;

function walk(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
        const full = path.join(directory, entry.name);
        if (entry.isDirectory()) walk(full);
        if (entry.isFile() && entry.name.endsWith('.php')) {
            checked += 1;
            try {
                parser.parseCode(fs.readFileSync(full, 'utf8'), full);
            } catch (error) {
                failures.push(`${full}: ${error.message}`);
            }
        }
    }
}

for (const root of roots) walk(root);

if (failures.length) {
    process.stderr.write(`${failures.join('\n')}\n`);
    process.exit(1);
}

process.stdout.write(`PHP syntax parsed: ${checked} files\n`);
