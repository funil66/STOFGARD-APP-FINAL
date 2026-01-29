import puppeteer from 'puppeteer';
import fs from 'fs';

const args = process.argv.slice(2);
const inputFile = args[0];
const outputFile = args[1];

if (!inputFile || !outputFile) {
    console.error('Uso: node generate-pdf.js <input.html> <output.pdf>');
    process.exit(1);
}

(async () => {
    try {
        const browser = await puppeteer.launch({
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });

        const page = await browser.newPage();
        
        // Carrega o HTML
        const htmlContent = fs.readFileSync(inputFile, 'utf8');
        await page.setContent(htmlContent, { waitUntil: 'networkidle0' });

        // Gera o PDF
        await page.pdf({
            path: outputFile,
            format: 'A4',
            printBackground: true,
            // O PULO DO GATO: Desligar header nativo para não duplicar com o seu HTML
            displayHeaderFooter: false, 
            margin: {
                top: '10mm',    // Margens menores pois seu HTML já tem padding
                bottom: '10mm',
                left: '0mm',
                right: '0mm'
            }
        });

        await browser.close();
        console.log('PDF gerado com sucesso!');
    } catch (error) {
        console.error('Erro ao gerar PDF:', error);
        process.exit(1);
    }
})();
