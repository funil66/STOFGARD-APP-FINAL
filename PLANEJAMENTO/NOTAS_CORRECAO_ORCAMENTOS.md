Notas de implantação - Correções de Orçamentos e Financeiro

- Após deploy das correções (QR, observers, comandos), execute para recalcular e persistir os totais dos orçamentos existentes:

```bash
php artisan stofgard:recalc-orcamentos
```

- Verifique também as permissões do diretório `storage/` (sessões e cache) e do arquivo de banco `database/database.sqlite` se estiver usando SQLite.

- Para disponibilizar gráficos financeiros na UI: use a rota autenticada `/financeiro/grafico/categoria?inicio=YYYY-MM-DD&fim=YYYY-MM-DD` que retorna JSON com soma por categoria no período.

- Testes adicionados: `StaticPixQrCodeTest` garante que o QR é salvo como Data URI; há testes para o Observer de Orçamento.