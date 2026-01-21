#!/usr/bin/env node
import fs from 'fs';
import path from 'path';
import puppeteer from 'puppeteer';

async function main() {
    const args = process.argv.slice(2);
    if (args.length < 2) {
        console.error('Usage: generate-pdf.js input.html output.pdf [format]');
        process.exit(2);
    }
    const [inputPath, outputPath, format = 'A4'] = args;
    const absoluteInput = path.isAbsolute(inputPath) ? inputPath : path.resolve(process.cwd(), inputPath);
    if (!fs.existsSync(absoluteInput)) {
        console.error('Input file not found:', absoluteInput);
        process.exit(3);
    }

    let browser;
    try {
        browser = await puppeteer.launch({
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
            headless: true,
        });
        const page = await browser.newPage();
        await page.goto('file://' + absoluteInput, { waitUntil: 'networkidle0' });
        await page.emulateMediaType('screen');
        await page.pdf({ path: outputPath, format, printBackground: true });
        console.log('PDF generated:', outputPath);
        await browser.close();
        process.exit(0);
    } catch (err) {
        console.error('Error generating PDF:', err?.message || err);
        if (browser) await browser.close();
        process.exit(1);
    }
}

main();
