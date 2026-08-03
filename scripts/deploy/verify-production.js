// Post-cutover accessibility check against the live site.
// Run from a directory where puppeteer + axe-core are installed:
//   cd /home/lukasz/.claude/jobs/52da36a6/tmp && node <repo>/scripts/deploy/verify-production.js
const fs = require('node:fs');
const os = require('node:os');
// Aliased: the page loop below binds a local `path` for the URL.
const nodePath = require('node:path');
const puppeteer = require('puppeteer');
const { source: axeSource } = require('axe-core');

const BASE = 'https://kzmielec.pl';
const CHROME =
    '/home/lukasz/.claude/jobs/52da36a6/tmp/.cache/chrome-headless-shell/linux-151.0.7922.47/chrome-headless-shell-linux64/chrome-headless-shell';
const PAGES = [
    ['home', '/'],
    ['belief-hub', '/w-co-wierzymy/'],
    ['belief-page', '/wizja/'],
    ['comparison', '/roznica-wyznan/'],
    ['meetings', '/zaplanuj-wizyte/'],
    ['position', '/w-sprawie-wieczerzy-panskiej/'],
    ['prawo-pdf', '/prawo/'],
    ['historia', '/historia-zboru-w-mielcu/'],
    ['rodo', '/rodo/'],
    ['ochrona-dzieci', '/polityka-ochrony-dzieci-przed-krzywdzeniem/'],
    ['search', '/?s=niedziela'],
    ['404', '/nie-ma-takiej-strony-xyz/'],
];
const VIEWPORTS = [
    ['desktop', 1280, 900],
    ['mobile', 390, 844],
    ['narrow', 320, 700],
];
const AA_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'shell',
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const violations = [];
    const overflow = [];
    let scans = 0;

    for (const [vpName, width, height] of VIEWPORTS) {
        for (const [pageName, path] of PAGES) {
            const page = await browser.newPage();
            await page.setViewport({ width, height });
            const label = `${vpName}/${pageName}`;
            try {
                await page.goto(BASE + path, { waitUntil: 'networkidle2', timeout: 60000 });
                const box = await page.evaluate(() => ({
                    scrollWidth: document.documentElement.scrollWidth,
                    clientWidth: document.documentElement.clientWidth,
                }));
                if (box.scrollWidth > box.clientWidth + 1) {
                    overflow.push({ label, ...box });
                }
                await page.evaluate(axeSource);
                const res = await page.evaluate(
                    async (tags) =>
                        await window.axe.run(document, { runOnly: { type: 'tag', values: tags } }),
                    AA_TAGS
                );
                scans++;
                for (const v of res.violations) {
                    violations.push({
                        label,
                        id: v.id,
                        nodes: v.nodes.length,
                        sample: v.nodes[0]?.html?.slice(0, 120),
                        summary: v.nodes[0]?.failureSummary?.split('\n')[1]?.trim(),
                    });
                }
            } catch (e) {
                violations.push({ label, id: 'SCAN-ERROR', sample: String(e).slice(0, 160) });
            }
            await page.close();
        }
    }
    await browser.close();

    // The system temp directory: a scan result is an artefact of one run, so it
    // belongs neither in the repository nor in a deploy scratch directory that
    // gets deleted at the end of a cutover.
    const report = nodePath.join(os.tmpdir(), 'kzmielec-verify-results.json');
    fs.writeFileSync(report, JSON.stringify({ scans, violations, overflow }, null, 1));
    console.log(`raport: ${report}`);
    console.log(`scans: ${scans}`);
    console.log(`AA violations: ${violations.length}`);
    const byId = {};
    for (const v of violations) byId[v.id] = (byId[v.id] || 0) + 1;
    console.log('by rule:', JSON.stringify(byId));
    for (const v of violations.slice(0, 12)) {
        console.log(`  ${v.label}  ${v.id}  x${v.nodes ?? '-'}  ${v.summary ?? v.sample ?? ''}`);
    }
    console.log(`horizontal overflow: ${overflow.length}`);
    for (const o of overflow) {
        console.log(`  ${o.label}: ${o.scrollWidth} > ${o.clientWidth}`);
    }
    process.exit(violations.length === 0 && overflow.length === 0 ? 0 : 1);
})();
