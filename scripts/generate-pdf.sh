#!/usr/bin/env bash
# Simples gerador de PDF de teste (cria um arquivo PDF mínimo a partir do HTML de entrada)
set -e
HTML_PATH="$1"
PDF_PATH="$2"

if [ -z "$HTML_PATH" ] || [ -z "$PDF_PATH" ]; then
  echo "Usage: $0 <html-path> <pdf-path>" >&2
  exit 2
fi

# Gera um PDF mínimo (válido para testes). Não usa ferramentas externas complexas.
cat > "$PDF_PATH" <<'PDF'
%PDF-1.4
%âãÏÓ
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 24 Tf
100 700 Td
(Test PDF) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000010 00000 n 
0000000061 00000 n 
0000000116 00000 n 
0000000181 00000 n 
trailer
<< /Root 1 0 R /Size 5 >>
startxref
260
%%EOF
PDF

exit 0
