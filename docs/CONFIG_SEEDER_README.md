# ConfigSeed - Seeder de Configurações Personalizadas

Este seeder preserva todas as configurações customizadas do sistema STOFGARD que foram definidas pelo usuário.

## 📋 O que o ConfigSeed faz

O seeder contém **todas as configurações necessárias** para restaurar o sistema após um reset da base de dados:

### 🏢 Dados da Empresa
- Nome: STOFGARD HIGIENIZAÇÃO E IMPERMEABILIZAÇÃO
- CNPJ: 58.794.846/0001-20
- Telefone: (16) 99753-9698
- Email: contato@stofgard.com.br
- Endereço completo
- Logo personalizada

### 🎯 Configurações do Dashboard
- Frase de boas-vindas: "BORA TRABALHAR!"
- Widget de clima habilitado
- URL do clima personalizada

### 📋 Catálogo de Serviços
- **53 tipos de serviços** completos com preços
- Móveis (cadeiras, poltronas, sofás, camas)
- Veículos (carros, caminhões, SUVs)
- Itens especiais (tapetes, cortinas, brinquedos)
- Preços diferenciados para higienização e impermeabilização

### 📄 Configurações do PDF
- Cores personalizadas (primária, secundária, texto)
- Layout customizado com 7 seções
- Configurações de exibição (fotos, PIX, descontos)

### 💰 Configurações Financeiras
- **3 chaves PIX** cadastradas
- Desconto à vista: 3%
- Parcelamento até 6x com taxas progressivas

## 🚀 Como Usar

### Rodar apenas o ConfigSeed
```bash
php artisan db:seed --class=ConfigSeed
```

### Rodar com todos os seeders (recomendado)
```bash
php artisan migrate:fresh --seed
```

## 📊 Resultados Esperados

Após executar o seeder:
- ✅ 1 registro em `configuracoes`
- ✅ 25 registros em `settings`
- ✅ Sistema totalmente configurado e pronto para uso

## ⚙️ Detalhes Técnicos

- **Arquivo:** `database/seeders/ConfigSeed.php`
- **Modelos utilizados:** `App\Models\Configuracao`, `App\Models\Setting`
- **Método:** `updateOrCreate()` - não duplica configurações
- **Incluso no:** `DatabaseSeeder.php` - roda automaticamente

## 🔄 Backup das Configurações

Todas as configurações foram capturadas em **26/01/2026** e incluem:

1. **Configurações básicas** (chave clima)
2. **Informações corporativas** completas
3. **Catálogo de serviços** detalhado (JSON)
4. **Layout do PDF** personalizado (JSON)
5. **Dados financeiros** (PIX, parcelamento, descontos)
6. **Tipos de serviço** do sistema (JSON)

## 🎨 Customizações Preservadas

- Logo da empresa personalizada
- Cores do PDF definidas
- Frase motivacional no dashboard
- URL específica do clima
- Layout de PDF com 7 seções
- Parcelamento com taxas customizadas
- Desconto à vista configurado
- Chaves PIX dos responsáveis

---

✨ **Este seeder garante que todas as personalizações sejam preservadas mesmo após resets do banco!**