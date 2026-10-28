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

## 4. Estratégia de Sincronização e Resiliência Offline

Quando o operador está em áreas sem sinal de internet (subsolos, áreas rurais ou galpões metálicos blindados):
1. A operação de entrega ou devolução é gravada na tabela local `operacoes_pendentes` do Room com carimbo de tempo (`timestamp`) e UUID único de idempotência.
2. O recibo local é gerado e assinado com os dados em cache do colaborador.
3. O `SyncWorker` do Android monitora o estado da rede via `NetworkCapabilities`.
4. Assim que a conexão for restabelecida, o lote de operações pendentes é enviado de forma ordenada e atômica para o endpoint `POST /entregas` e `POST /devolucoes`, confirmando o registro no banco MySQL Cloud.
