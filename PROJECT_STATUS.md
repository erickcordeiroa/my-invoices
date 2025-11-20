# 📊 My Invoices - Controle Financeiro

## 📋 Visão Geral do Projeto

Sistema de controle financeiro básico de fluxo de caixa com separação por carteira. Permite lançar contas a pagar e a receber de forma única, parcelada ou mensal (com geração automática).

---

## ✅ O QUE JÁ ESTÁ IMPLEMENTADO

### 🔐 Autenticação e Autorização
- ✅ **Registro de usuário** (`POST /v1/register`)
  - Validação de dados
  - Criação de usuário com status 'pending'
  - Geração de token de ativação
  - Envio de email de ativação via fila (ActivateMailJob)

- ✅ **Ativação de conta** (`POST /v1/activate`)
  - Validação de token
  - Ativação de usuário (status: 'active')
  - Expiração de token (24 horas)

- ✅ **Login** (`POST /v1/login`)
  - Autenticação com email e senha
  - Geração de token Sanctum
  - Validação de status do usuário (pending/inactive)

- ✅ **Recuperação de senha** (`POST /v1/forgot-password`)
  - Geração de token de reset
  - Envio de email via fila (ResetPasswordMailJob)

- ✅ **Redefinição de senha** (`POST /v1/reset-password`)
  - Validação de token
  - Atualização de senha

- ✅ **Logout** (`POST /v1/logout`)
  - Revogação de todos os tokens do usuário

### 📁 Categorias
- ✅ **Listagem** (`GET /v1/categories`)
  - Paginação com cursor
  - Filtro por usuário autenticado

- ✅ **Busca** (`GET /v1/categories/search`)
  - Busca por nome (parcial)
  - Filtro por tipo (income/expense)

- ✅ **Detalhes** (`GET /v1/categories/{id}`)
  - Validação de propriedade

- ✅ **Criação** (`POST /v1/categories`)
  - Validação de dados
  - Verificação de duplicidade
  - Associação com usuário

- ✅ **Atualização** (`PUT /v1/categories/{id}`)
  - Validação de propriedade
  - Validação de duplicidade

- ✅ **Exclusão** (`DELETE /v1/categories/{id}`)
  - Validação de propriedade
  - Verificação de movimentações vinculadas

### 🗄️ Estrutura de Banco de Dados
- ✅ **Tabela `users`**
  - Campos: name, email, password, status, email_verified_at
  - Soft deletes

- ✅ **Tabela `categories`**
  - Campos: user_id, name, type (income/expense)
  - Soft deletes
  - Índices otimizados

- ✅ **Tabela `wallets`**
  - Campos: user_id, name, balance
  - Soft deletes

- ✅ **Tabela `invoices`**
  - Campos: user_id, wallet_id, category_id, invoice_of, description, type, amount, currency, due_at, paid_at, repeat_when, period, enrollments, enrollments_of, status
  - Soft deletes
  - Suporte para parcelas e recorrência

- ✅ **Tabela `account_activations`**
  - Campos: user_id, token, expires_at, activated_at
  - Suporte para ativação de conta

- ✅ **Tabela `personal_access_tokens`** (Sanctum)
  - Gerenciamento de tokens de autenticação

### 📧 Sistema de Emails
- ✅ **ActivateMailJob**
  - Job para envio de email de ativação
  - Processamento via fila

- ✅ **ResetPasswordMailJob**
  - Job para envio de email de recuperação de senha
  - Processamento via fila

### 🏗️ Arquitetura
- ✅ **Padrão de DTOs** (Data Transfer Objects)
  - Separação de responsabilidades
  - Validação de dados

- ✅ **Padrão de Services**
  - Lógica de negócio isolada
  - Reutilização de código

- ✅ **Padrão de Exceptions customizadas**
  - Tratamento de erros específicos
  - Mensagens claras

- ✅ **Padrão de Resources** (API Resources)
  - Formatação de respostas
  - Transformação de dados

- ✅ **Request Validation**
  - Validação de entrada
  - Regras de negócio

---

## 🚧 TODO - O QUE FALTA IMPLEMENTAR

### 💰 Carteiras (Wallets)
- [ ] **Controller** (`WalletController`)
  - [ ] Listagem de carteiras (`GET /v1/wallets`)
  - [ ] Detalhes da carteira (`GET /v1/wallets/{id}`)
  - [ ] Criação de carteira (`POST /v1/wallets`)
  - [ ] Atualização de carteira (`PUT /v1/wallets/{id}`)
  - [ ] Exclusão de carteira (`DELETE /v1/wallets/{id}`)
  - [ ] Busca de carteiras (`GET /v1/wallets/search`)

- [ ] **Service** (`WalletServices`)
  - [ ] Lógica de negócio para CRUD
  - [ ] Validação de propriedade
  - [ ] Cálculo automático de saldo baseado em invoices

- [ ] **DTOs** (`WalletDTO`)
  - [ ] StoreUpdateWalletDTO
  - [ ] SearchWalletDTO

- [ ] **Requests** (`WalletRequest`)
  - [ ] StoreUpdateWalletRequest
  - [ ] SearchWalletRequest

- [ ] **Resource** (`WalletResource`)
  - [ ] Formatação de resposta

- [ ] **Exception** (`WalletException`)
  - [ ] Tratamento de erros específicos

- [ ] **Rotas** (`routes/api.php`)
  - [ ] Adicionar rotas de carteiras

### 📄 Faturas/Invoices (Contas a Pagar e Receber)
- [ ] **Controller** (`InvoiceController`)
  - [ ] Listagem de invoices (`GET /v1/invoices`)
  - [ ] Detalhes da invoice (`GET /v1/invoices/{id}`)
  - [ ] Criação de invoice (`POST /v1/invoices`)
  - [ ] Atualização de invoice (`PUT /v1/invoices/{id}`)
  - [ ] Exclusão de invoice (`DELETE /v1/invoices/{id}`)
  - [ ] Busca de invoices (`GET /v1/invoices/search`)
  - [ ] Marcar como pago (`POST /v1/invoices/{id}/pay`)
  - [ ] Marcar como não pago (`POST /v1/invoices/{id}/unpay`)

- [ ] **Service** (`InvoiceServices`)
  - [ ] Lógica de negócio para CRUD
  - [ ] Validação de propriedade
  - [ ] Criação de invoice única
  - [ ] Criação de invoice parcelada (gerar múltiplas invoices relacionadas)
  - [ ] Criação de invoice mensal (configurar recorrência)
  - [ ] Atualização de saldo da carteira ao pagar/receber
  - [ ] Validação de carteira e categoria

- [ ] **DTOs** (`InvoiceDTO`)
  - [ ] StoreUpdateInvoiceDTO
  - [ ] SearchInvoiceDTO
  - [ ] PayInvoiceDTO

- [ ] **Requests** (`InvoiceRequest`)
  - [ ] StoreUpdateInvoiceRequest
  - [ ] SearchInvoiceRequest
  - [ ] PayInvoiceRequest

- [ ] **Resource** (`InvoiceResource`)
  - [ ] Formatação de resposta
  - [ ] Inclusão de relacionamentos (wallet, category)

- [ ] **Exception** (`InvoiceException`)
  - [ ] Tratamento de erros específicos

- [ ] **Rotas** (`routes/api.php`)
  - [ ] Adicionar rotas de invoices

### 🔄 Sistema de Recorrência (Faturas Mensais)
- [ ] **Job** (`GenerateRecurringInvoicesJob`)
  - [ ] Verificar invoices com `repeat_when = 'monthly'`
  - [ ] Gerar novas invoices automaticamente
  - [ ] Atualizar `enrollments_of` e `enrollments`
  - [ ] Parar quando atingir o número total de parcelas

- [ ] **Command** (`php artisan invoices:generate-recurring`)
  - [ ] Comando para executar geração de invoices recorrentes
  - [ ] Agendar no cron (diário)

- [ ] **Lógica de geração automática**
  - [ ] Verificar data de vencimento
  - [ ] Criar nova invoice com data do próximo mês
  - [ ] Manter relacionamento com invoice original (`invoice_of`)
  - [ ] Atualizar contadores de parcelas

### 📊 Relatórios
- [ ] **Controller** (`ReportController`)
  - [ ] Relatório de fluxo de caixa (`GET /v1/reports/cash-flow`)
  - [ ] Exportar relatório em PDF (`POST /v1/reports/export`)

- [ ] **Service** (`ReportServices`)
  - [ ] Filtros implementados:
    - [ ] Por data (período: início e fim)
    - [ ] Por categoria
    - [ ] Por carteira
    - [ ] Por status (pago/não pago)
    - [ ] Por tipo (income/expense)
  - [ ] Cálculo de valores totais:
    - [ ] Total de receitas
    - [ ] Total de despesas
    - [ ] Saldo (receitas - despesas)
    - [ ] Total por categoria
    - [ ] Total por carteira
  - [ ] Agrupamento de dados
  - [ ] Ordenação

- [ ] **DTOs** (`ReportDTO`)
  - [ ] CashFlowReportDTO
  - [ ] ExportReportDTO

- [ ] **Requests** (`ReportRequest`)
  - [ ] CashFlowReportRequest
  - [ ] ExportReportRequest

- [ ] **Resource** (`ReportResource`)
  - [ ] Formatação de dados do relatório

- [ ] **Job** (`GenerateReportPdfJob`)
  - [ ] Geração de PDF via fila
  - [ ] Usar biblioteca de PDF (ex: DomPDF, Snappy)
  - [ ] Template de relatório
  - [ ] Salvar PDF no storage
  - [ ] Notificar usuário quando PDF estiver pronto

- [ ] **Rotas** (`routes/api.php`)
  - [ ] Adicionar rotas de relatórios

### 📧 Sistema de Avisos de Vencimento
- [ ] **Job** (`SendDueDateReminderJob`)
  - [ ] Verificar invoices próximas do vencimento
  - [ ] Filtrar por:
    - [ ] Status: 'unpaid' ou 'overdue'
    - [ ] Data de vencimento (ex: próximos 7 dias)
  - [ ] Agrupar por usuário
  - [ ] Enviar email com relatório

- [ ] **Mail** (`DueDateReminderMail`)
  - [ ] Template de email
  - [ ] Lista de invoices a vencer (contas a pagar)
  - [ ] Lista de invoices a receber (contas a receber)
  - [ ] Total de valores a pagar
  - [ ] Total de valores a receber
  - [ ] Saldo previsto

- [ ] **Command** (`php artisan invoices:send-due-reminders`)
  - [ ] Comando para executar envio de avisos
  - [ ] Agendar no cron (diário)

- [ ] **Service** (`ReminderServices`)
  - [ ] Lógica para buscar invoices próximas do vencimento
  - [ ] Agrupamento de dados
  - [ ] Cálculo de totais

- [ ] **Configuração**
  - [ ] Configurar dias de antecedência para aviso
  - [ ] Permitir configuração por usuário

### 📱 WhatsApp (Futuro)
- [ ] **Integração com API de WhatsApp**
  - [ ] Escolher provedor (Twilio, WhatsApp Business API, etc.)
  - [ ] Configurar credenciais

- [ ] **Job** (`SendDueDateReminderWhatsAppJob`)
  - [ ] Enviar avisos via WhatsApp
  - [ ] Formatação de mensagem
  - [ ] Template de mensagem

- [ ] **Service** (`WhatsAppServices`)
  - [ ] Lógica de envio
  - [ ] Tratamento de erros
  - [ ] Rate limiting

- [ ] **Configuração**
  - [ ] Permitir usuário escolher canal (email/WhatsApp/ambos)
  - [ ] Configuração de preferências

### 👤 Perfil do Usuário
- [ ] **Controller** (`ProfileController`)
  - [ ] Obter perfil (`GET /v1/profile`)
  - [ ] Atualizar perfil (`PUT /v1/profile`)
  - [ ] Alterar senha (`PUT /v1/profile/password`)

- [ ] **Service** (`ProfileServices`)
  - [ ] Lógica de atualização
  - [ ] Validação de senha atual
  - [ ] Atualização de dados

- [ ] **DTOs** (`ProfileDTO`)
  - [ ] UpdateProfileDTO
  - [ ] UpdatePasswordDTO

- [ ] **Requests** (`ProfileRequest`)
  - [ ] UpdateProfileRequest
  - [ ] UpdatePasswordRequest

- [ ] **Resource** (`ProfileResource`)
  - [ ] Formatação de dados do perfil

- [ ] **Rotas** (`routes/api.php`)
  - [ ] Adicionar rotas de perfil

### 🔧 Melhorias e Ajustes
- [ ] **Validações adicionais**
  - [ ] Validar se carteira pertence ao usuário ao criar invoice
  - [ ] Validar se categoria pertence ao usuário ao criar invoice
  - [ ] Validar tipo de categoria (income/expense) com tipo de invoice
  - [ ] Validar saldo da carteira ao pagar invoice

- [ ] **Atualização automática de status**
  - [ ] Marcar invoice como 'overdue' quando passar da data de vencimento
  - [ ] Job para verificar invoices vencidas diariamente

- [ ] **Soft deletes**
  - [ ] Verificar se todos os modelos estão usando SoftDeletes corretamente
  - [ ] Implementar restauração de registros deletados (se necessário)

- [ ] **Testes**
  - [ ] Testes unitários para Services
  - [ ] Testes de integração para Controllers
  - [ ] Testes de Jobs
  - [ ] Testes de Commands

- [ ] **Documentação da API**
  - [ ] Swagger/OpenAPI
  - [ ] Postman Collection

- [ ] **Performance**
  - [ ] Otimização de queries (eager loading)
  - [ ] Índices adicionais no banco de dados
  - [ ] Cache de consultas frequentes

- [ ] **Segurança**
  - [ ] Rate limiting nas rotas
  - [ ] Validação de permissões
  - [ ] Sanitização de inputs

---

## 📝 Notas de Implementação

### Estrutura de Invoice (Fatura)
A tabela `invoices` já possui campos para suportar:
- **Invoice única**: `enrollments = null`, `enrollments_of = null`, `invoice_of = null`
- **Invoice parcelada**: `enrollments = N`, `enrollments_of = X`, `invoice_of = ID da primeira invoice`
- **Invoice mensal**: `repeat_when = 'monthly'`, `period = 'monthly'`, `enrollments = N`

### Fluxo de Criação de Invoice Parcelada
1. Usuário cria invoice com `enrollments = 3` (exemplo)
2. Sistema cria 3 invoices relacionadas:
   - Invoice 1: `invoice_of = null`, `enrollments = 3`, `enrollments_of = 1`
   - Invoice 2: `invoice_of = 1`, `enrollments = 3`, `enrollments_of = 2`
   - Invoice 3: `invoice_of = 1`, `enrollments = 3`, `enrollments_of = 3`
3. Cada invoice tem `due_at` incrementado conforme o período

### Fluxo de Criação de Invoice Mensal
1. Usuário cria invoice com `repeat_when = 'monthly'` e `enrollments = 12` (exemplo)
2. Sistema cria primeira invoice
3. Job diário verifica invoices com `repeat_when = 'monthly'` e `enrollments_of < enrollments`
4. Gera nova invoice para o próximo mês
5. Atualiza `enrollments_of`
6. Para quando `enrollments_of = enrollments`

### Atualização de Saldo da Carteira
- Ao marcar invoice como paga (`paid_at` preenchido):
  - Se `type = 'income'`: `wallet.balance += invoice.amount`
  - Se `type = 'expense'`: `wallet.balance -= invoice.amount`
- Ao desmarcar invoice como paga:
  - Reverter a operação acima

---

## 🎯 Prioridades de Implementação

### Fase 1 - Funcionalidades Core
1. CRUD de Carteiras (Wallets)
2. CRUD de Invoices (básico)
3. Sistema de parcelas
4. Sistema de recorrência mensal

### Fase 2 - Relatórios e Exportação
1. Sistema de relatórios com filtros
2. Exportação em PDF via fila

### Fase 3 - Notificações
1. Sistema de avisos de vencimento por email
2. (Futuro) Sistema de avisos via WhatsApp

### Fase 4 - Melhorias
1. Perfil do usuário
2. Testes
3. Documentação
4. Otimizações

---

**Última atualização**: 2025-01-XX
**Versão do projeto**: 0.1.0

