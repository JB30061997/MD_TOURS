import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const root = dirname(fileURLToPath(import.meta.url));
const pageUrl = `file://${join(root, 'index.html')}`;
const exportDir = join(root, 'exports');

const formats = [
    { name: 'square', width: 1080, height: 1080 },
    { name: 'story', width: 1080, height: 1920 },
    { name: 'wide', width: 1200, height: 628 },
];

await mkdir(exportDir, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 900 }, deviceScaleFactor: 1 });
await page.goto(pageUrl);

const screens = await page.$$eval('[data-export]', (nodes) =>
    nodes.map((node) => ({
        id: node.id,
        exportName: node.getAttribute('data-export') || node.id,
    })),
);

for (const format of formats) {
    await page.setViewportSize({ width: format.width, height: format.height });

    for (const screen of screens) {
        const selector = `#${screen.id}`;
        await page.locator(selector).scrollIntoViewIfNeeded();
        await page.locator(selector).screenshot({
            path: join(exportDir, `${screen.exportName}-${format.name}.png`),
        });
    }
}

await browser.close();

console.log(`Exported ${screens.length * formats.length} screenshots to ${exportDir}`);
