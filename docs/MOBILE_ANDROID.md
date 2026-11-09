# Módulo Mobile Android — Gestão EPI

Este documento detalha o aplicativo nativo para a plataforma Android do ecossistema **Gestão EPI**, projetado para uso por técnicos de SST, encarregados e almoxarifes em canteiros de obra, fábricas e áreas operacionais.

---

## 1. Visão Geral e Arquitetura do App

O aplicativo foi desenvolvido com foco em alta disponibilidade, ergonomia de uso com uma só mão e capacidade de **operação contínua mesmo sem conectividade com a internet (Offline-First)**:

* **Linguagem Principal:** Kotlin e Java Moderno (Android SDK 24+ / Android 7.0 até Android 15).
* **Arquitetura de Apresentação:** MVVM (Model-View-ViewModel) com Jetpack Lifecycle e View Binding.
* **Cliente de Rede:** Retrofit 2 + OkHttp 3 com interceptadores automáticos para injeção de token JWT e cabeçalho `X-Device-Info`.
* **Banco de Dados Local:** Room Database (SQLite embutido) para armazenamento local e fila transacional de operações pendentes.
* **Sincronização em Segundo Plano:** WorkManager (`SyncManager` e `SyncWorker`) com rotina periódica e por gatilho de conectividade de rede.

```text
[Interface Android (Views/Activities)]
           │
           ▼
[ViewModels & LiveData]
           │
           ▼
[Repository Pattern (GestaoEpiRepository)]
      ┌────┴────────────────────────┐
      ▼                             ▼
[Room Database Local]     [Retrofit 2 HTTP Client]
  • Funcionários Cache      • Interceptor Bearer JWT
  • Catálogo de EPIs        • Endpoints /auth, /entregas
  • Fila Operações Offline  • X-Device-Info Auditoria
      │                             │
      └──────► [WorkManager] ───────┘
            (Sincronização Cloud)
```

---

## 2. Assinatura Eletrônica e Autenticação de PIN de 6 Dígitos

Uma das funcionalidades mais críticas do aplicativo é a coleta da assinatura do colaborador no momento da entrega do EPI no campo:

### 2.1 Regra Criptográfica de Validação de PIN (`HashUtils.java`)
Para garantir integridade jurídica em auditorias do MTE (NR-6), o PIN numérico digitado pelo colaborador não trafega nem é salvo em texto puro:
1. O banco central armazena o `salt` exclusivo de 16 bytes em hexadecimal gerado aleatoriamente no cadastro do colaborador.
2. O aplicativo calcula localmente ou envia para validação a operação:
   $$\text{HashGerado} = \text{SHA-256}(\text{PIN} + \text{Salt})$$
3. A string resultante de 64 caracteres em hexadecimal minúsculo é comparada com `hash_pin` no banco central.
4. Essa implementação é **100% simétrica** à rotina do `SecurityService.php` no Painel Web, garantindo que o mesmo PIN funcione de forma intercambiável no aplicativo e no portal web.

### 2.2 PIN Padrão Inicial
Novos colaboradores criados no sistema possuem PIN padrão inicial `123456`, com status `ATIVO`, permitindo que o técnico realize a entrega imediatamente ou oriente o colaborador na personalização de seu código secreto.

---

## 3. Gestão e Rastreabilidade de Lotes no App

Em conformidade com a versão 8.4.4 do ecossistema:
* Ao selecionar um equipamento para entrega pelo aplicativo móvel, o campo de **Lote** é carregado automaticamente com o lote registrado no catálogo do EPI (`epi_numero_lote`) ou identificado pelo código de patrimônio.
* O aplicativo impede a finalização de qualquer entrega cujo lote esteja vazio ou não preenchido, garantindo a conformidade antes do envio para a fila de sincronização.

---

## 4. Telas Principais do Módulo Mobile

1. **Autenticação (Login):**
   - Entrada com credenciais de operador (`usu_login` e `senha`).
   - Armazenamento seguro de token JWT em `EncryptedSharedPreferences`.
2. **Dashboard de Campo:**
   - Resumo rápido de entregas do dia, pendências de sincronização offline e atalhos para nova entrega.
3. **Nova Entrega de EPI com Teclado Numérico de PIN:**
   - Seleção rápida de funcionário por busca ou QR Code do crachá.
   - Seleção de múltiplos EPIs com validação de C.A. e Lote obrigatório.
   - Teclado PIN customizado de 6 dígitos em tela cheia com feedback tátil.
4. **Catálogo de EPIs e Consulta de C.A.:**
   - Consulta instantânea offline dos Certificados de Aprovação e saldos de estoque.
5. **Devoluções de Campo:**
   - Listagem dos EPIs em custódia do empregado e baixa imediata com foto do estado de conservação (opcional).

---

## 5. Resiliência Offline e Mecanismo de Sincronização

Quando o operador está em áreas sem sinal de internet (subsolos, áreas rurais ou túneis):
1. O aplicativo gera um identificador único universal (UUID v4) para cada entrega ou devolução.
2. A operação é gravada transacionalmente no Room Database local com status `PENDENTE_SYNC`.
3. O `SyncWorker` agenda tentativas com recuo exponencial (*exponential backoff*).
4. Ao restabelecer conexão (Wi-Fi ou 4G/5G), os pacotes são transmitidos em lote para `/sync/push`.
5. O backend processa cada UUID de forma idempotente, impedindo duplicidade de registros.

---

## 6. Conformidade com Diretrizes do Brasil

* **Localização Brasil:** Padrão estrito de idioma `pt-BR`, moeda em Real (`R$`), datas em `DD/MM/AAAA` e fuso horário oficial `America/Sao_Paulo` (GMT-3).
* **Paridade de Regras:** 100% das regras de cálculo de KPIs, controle de lotes da NR-6 e proteção de dados LGPD operam de maneira idêntica entre o App Android e o Painel Web Administrativo.

---

## 7. Garantia de Operação, Estabilidade e Governança (Release 8.4.8 / 8.4.9)

* **Desacoplamento e Segurança:** O aplicativo Android opera de forma 100% desacoplada das tecnologias de apresentação web (PHP, Apache, `.htaccess` e cookies de navegador).
* **Validação de PIN e Sincronização:** Os algoritmos de validação de assinatura digital (`HashUtils.java` com `SHA-256 + Salt`) permanecem 100% alinhados à classe `SecurityService.php`.
* **Sem Necessidade de Recompilação:** Nenhuma das melhorias de segurança ou de governança CI/CD implementadas na Web impacta a camada de rede do Android (Retrofit 2), mantendo a versão de produção do aplicativo em pleno funcionamento.
* **Governança de Sincronização:** Os endpoints `/sync/push` e `/sync/pull` continuam operando sob estrita conformidade com os schemas definidos em [API_REST_ENDPOINTS.md](API_REST_ENDPOINTS.md).
