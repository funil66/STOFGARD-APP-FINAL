# Validação do Certificado de Garantia (QR Code)

## Visão geral
O certificado de garantia gera um selo digital com:
- hash SHA-256 do documento;
- QR Code apontando para a URL pública de validação;
- dados de emissão (empresa, tipo e ID do documento).

## Fluxo técnico
1. O PDF de garantia é renderizado em `resources/views/pdf/certificado_garantia.blade.php`.
2. A view chama `App\Services\DigitalSealService::buildSealData('garantia', $osId)`.
3. O serviço gera:
   - `hash`
   - `validation_url` (`/validar/{hash}`)
   - `qr_base64`
4. O payload do selo é armazenado em cache por 2 anos (`digital_seal:{hash}`).
5. A rota pública `GET /validar/{hash}` consulta o cache e mostra o status de validade.

## Arquivos
- `app/Services/DigitalSealService.php`
- `app/Http/Controllers/DigitalSealValidationController.php`
- `resources/views/validacao/selo.blade.php`
- `routes/web.php`

## Cenário de teste manual
1. Gerar um certificado de garantia.
2. Ler o QR Code do rodapé.
3. Abrir a URL de validação.
4. Resultado esperado:
   - página com status `✅ Certificado válido`;
   - hash exibido igual ao hash do certificado.

## Observações
- Se o hash não existir no cache, o endpoint retorna `404` com mensagem de certificado não encontrado.
- Para ambientes com limpeza agressiva de cache, recomenda-se usar backend persistente (Redis) para não perder histórico de validação.
