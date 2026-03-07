# 🚀 Roadmap de Visão, Operação e Planos (Tiers)

## 🚁 1. OPERAÇÃO E LOGÍSTICA (O Controlo no Terreno)

### A. Check-in de Campo (Rastreio GPS)
- **A Dor:** Cliente contesta tempo de serviço ou dono quer auditar localização do técnico.
- **A Solução:** Botão "Iniciar Serviço" na OS pelo celular capta coordenadas GPS, IP e hora exata, gerando registro imutável.
- **Plano:** ELITE (Crucial para gestão de equipes).

### B. Gestão de "Estoque de Viatura" (Multi-Almoxarifado)
- **A Dor:** Técnico perde controle do estoque na carrinha (viatura) versus loja principal.
- **A Solução:** Múltiplos "Locais de Estoque". Débito automático do estoque da "Carrinha do João" ao fazer a OS.
- **Plano:** PRO e ELITE.

---

## 💰 2. MÁQUINA DE VENDAS E MARKETING (O Efeito Bola de Neve)

### C. Motor de Indicações (Cashback/Referral no Zap)
- **A Dor:** A principal fonte de clientes é o "boca-a-boca", que precisa ser estimulado.
- **A Solução:** 3 dias pós-serviço pago, envio de WhatsApp pedindo indicação com link único. Prêmio de 20% de desconto no próximo serviço para o cliente se o amigo fechar.
- **Plano:** ELITE (O software como equipe de marketing).

### D. Bot de Confirmação de Agendamento Inteligente
- **A Dor:** Cliente "esquece" do agendamento (o famoso bolo).
- **A Solução:** Aviso via Zap 24h antes pedindo confirmação (SIM/NÃO). Integração Evolution API + Webhook atualiza o Kanban automaticamente.
- **Plano:** PRO.

---

## 🏛️ 3. BUROCRACIA E GOVERNO (O Nível Enterprise)

### E. Emissão de NFS-e (Nota Fiscal de Serviço Automática)
- **A Dor:** Portais de prefeituras instáveis e trabalhosos.
- **A Solução:** Integração API (Focus NFe/eNotas). Aprovação e pagamento geram NF automática em PDF e enviam pelo WhatsApp.
- **Plano:** ELITE (Possível Add-on de R$ 50/mês).

---

## 🤖 4. O FUTURO (Inteligência Artificial)

### F. O "Oráculo" (Assistente de IA Integrado)
- **A Dor:** Dificuldade técnica pontual ou bloqueio na escrita de mensagens difíceis (ex: cobrança).
- **A Solução:** Widget com IA do Gemini no painel. Ex: "Gera um texto educado para cobrar a fatura atrasada", entregando respostas precisas.
- **Plano:** ELITE.

---

## 📊 ESTRUTURA DE PERMISSÕES E PLANOS (TIERS)

### 🥉 TIER 1: START (Plano de Entrada) - *Para quem está a sair do Excel*
- CRM e Cadastros Ilimitados
- Orçamentos e OS (limite de 30/mês)
- Agendamento manual
- PDF simples (com marca d'água STOFGARD / Autonomia Ilimitada)
- ❌ **Bloqueado:** WhatsApp automático, GPS, Portal do Cliente, Kanban.

### 🥈 TIER 2: PRO (O Carro-Chefe) - *Para quem quer ganhar tempo*
- **Tudo do START + sem limite de OS**
- Funil Kanban de Vendas
- Vitrine Pública (Link na Bio)
- Cobrança Automática via PIX (Asaas/EFI)
- Bot de Confirmação de Agenda no Zap
- Assinaturas Digitais Validadas (Hash)
- Gestão de "Estoque de Viatura"

### 🥇 TIER 3: ELITE / ESTÚDIO (Escala Total) - *Para equipas e empresas*
- **Tudo do PRO**
- Acesso Multi-utilizador (Patrão + ajudantes com permissões/roles)
- Portal White-label do Cliente Final
- Check-in por GPS nas Ordens de Serviço
- Motor de Indicações e Retenção Preditiva (Cashback)
- Sem marca d'água nos PDFs
- Módulo de Contratos e Recorrências
- Emissão de NFS-e (com ou sem Add-on)
- "Oráculo" (IA Integrada)

---
> **Nota de Engenharia:** A infraestrutura atual com *Filament* + *Tenancy* + *Spatie Roles & Permissions* (ou Traits de usuário) fornece a base perfeita para o enforcing destes limites baseados na assinatura (`tenant->plan`).
