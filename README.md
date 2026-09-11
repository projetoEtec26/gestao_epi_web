# Painel Administrativo Web — Gestão EPI

Este projeto é uma aplicação Web responsiva desenvolvida em **PHP 8+, HTML5, CSS3, JS e Bootstrap 5** para atuar como o módulo administrativo e gerencial unificado do ecossistema de conformidade **Gestão EPI**. Ele opera em sincronia em tempo real com o aplicativo Android corporativo e consome a API REST remota/local do ecossistema.

---

## 1. DIRETRIZES DE LOCALIZAÇÃO E CONFORMIDADE BRASIL

Conforme as diretrizes globais do projeto, toda a aplicação Web e suas documentações operam estritamente sob os padrões corporativos e legais do **Brasil**:

*   **Idioma Padrão:** Português do Brasil (`pt-BR`).
*   **Moeda Oficial:** Real Brasileiro (`BRL / R$`), formatado via helper global `formatarValorMonetario()`.
*   **Formato de Datas:** `DD/MM/AAAA` (com suporte a exibição de horário no padrão 24h `HH:mm:ss`).
*   **Fuso Horário Oficial:** `America/Sao_Paulo` (GMT-3), inicializado obrigatoriamente no topo de `config/api.php` e `components/header.php`.
*   **Legislação e Normas Aplicáveis:** Norma Regulamentadora **NR-6** (Ministério do Trabalho e Emprego), controle rigoroso de **C.A. (Certificado de Aprovação)** e conformidade com a **LGPD (Lei Geral de Proteção de Dados - Lei nº 13.709/2018)** na auditoria de exportações e mascaramento preventivo de dados sensíveis (CPF).

---

## 2. ARQUITETURA DO PROJETO E ESTRUTURA DE DIRETÓRIOS

O painel Web foi estruturado com uma arquitetura modular limpa (SoC - *Separation of Concerns*), eliminando acoplamentos diretos com o banco de dados para trafegar 100% dos dados por meio do cliente HTTP `ApiService`:

```text
gestao_epi_web_2/
│
├── index.php                             # Roteador de entrada de sessão (redireciona para Dashboard ou Login)
├── login.php                             # Login institucional e redefinição obrigatória no 1º acesso
├── recuperar-senha.php                   # Fluxo de redefinição de senhas provisórias via API REST
├── logout.php                            # Destruição segura da sessão PHP e token JWT no cliente/servidor
├── template_relatorio_php.php            # Template estruturado para geração e exportação de relatórios PHP
├── README.md                             # Documentação técnica oficial e arquitetural do projeto
│
├── config/
│   └── api.php                           # Centralização da URL base da API, fuso America/Sao_Paulo e APP_ROOT dinâmico
│
├── services/
│   └── ApiService.php                    # Client HTTP cURL centralizado com gestão JWT, retry e resiliência a timeouts
│
├── components/
│   ├── header.php                        # Cabeçalho HTML, importação do Design System, RBAC e fuso horário
│   ├── footer.php                        # Fechamento de layout e inclusão de bibliotecas (Bootstrap 5, Chart.js)
│   ├── sidebar.php                       # Barra lateral direta (tema escuro ciano, capacete EPI SVG e controle RBAC)
│   └── topbar.php                        # Barra superior com informações do usuário logado e alternador Dark Mode
│
├── assets/
│   ├── css/
│   │   └── style.css                     # Design System unificado (Light/Dark Mode via HSL, Glassmorphism, Autocomplete)
│   └── js/
│       └── main.js                       # Alternador de modo escuro, recolhimento de sidebar e máscaras (CPF, telefone)
│
├── pages/
│   ├── dashboard.php                    # Painel gerencial com KPIs, custos, consumo e gráficos dinâmicos (Chart.js)
│   ├── funcionarios.php                 # CRUD de funcionários, autocomplete multi-campo, avatares e modal de filtro
│   ├── epis.php                         # Catálogo de EPIs, reajuste em lote de preços, estoque e controle de C.A. (NR-6)
│   ├── entregas.php                     # Histórico de entregas de EPIs, filtro por status e Termo de Ciência assinado
│   ├── nova_entrega.php                 # Workflow em etapas de emissão de EPIs, validação de C.A. e assinatura digital
│   ├── devolucoes.php                   # Registro de devoluções de EPIs, inspeção de estado e retorno ao almoxarifado
│   ├── ficha_colaborador.php            # Ficha individual completa com histórico de entregas, posse atual e assinaturas
│   ├── relatorios.php                   # Central de relatórios gerenciais com autocomplete no filtro de colaborador
│   ├── relatorio_geral.php              # Relatório detalhado de fornecimento de EPIs com paginação e exportação
│   ├── relatorio_financeiro.php         # Demonstrativo financeiro de custos de EPI por departamento/centro de custo
│   ├── relatorio_consumo_epi.php        # Relatório de consumo quantitativo e financeiro de EPIs por período
│   ├── relatorio_validade_ca.php        # Relatório de controle de vencimento dos Certificados de Aprovação (C.A.)
│   ├── relatorio_auditoria_logs.php     # Relatório de logs de auditoria do sistema com visualizador JSON
│   ├── relatorio_auditoria_impressao.php# Modelo de impressão oficial A4 paisagem para auditorias fiscais/SST
│   ├── aceitar-termos.php               # Tela institucional de aceite dos termos de uso e políticas de privacidade
│   ├── usuarios.php                     # Gestão de operadores do sistema e atribuição de perfis RBAC (Exclusivo Admin)
│   ├── auditoria.php                    # Centro de auditoria de logs com decodificador JSON (Exclusivo Admin)
│   ├── configuracoes.php                # Perfil do usuário logado, alteração de senha e teste de status da API
│   ├── api_proxy.php                    # Gateway Server-to-Server (anti-CORS) para chamadas AJAX assíncronas
│   ├── 403.php                          # Página personalizada de Acesso Negado (Bloqueio RBAC)
│   └── 404.php                          # Página personalizada de Recurso Não Encontrado
│
└── scratch/                             # Scripts utilitários de benchmark e diagnósticos
    ├── benchmark_entregas.php           # Benchmark de velocidade da listagem de entregas
    ├── check_db.php                     # Verificação rápida de conexão com o banco de dados
    ├── render_funcionarios.php          # Teste de renderização e performance da lista de colaboradores
    ├── test_api.php                     # Validação de comunicação e conectividade com a API REST
    ├── test_db_speed.php                # Teste comparativo de velocidade de consultas SQL
    ├── test_entregas_fast.php           # Teste de otimização da busca em lote de itens de entrega
    ├── test_login_entregas.php          # Teste de autenticação JWT e obtenção de entregas
    └── update_api.php                   # Script utilitário para atualização/validação de endpoints
```

---

## 3. CONFIGURAÇÃO DA API (AMBIENTE) E RESILIÊNCIA HTTP

A integração HTTP entre o painel Web e o backend é gerenciada pela classe [`services/ApiService.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/services/ApiService.php) e configurada dinamicamente em [`config/api.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/config/api.php).

### 3.1 Resoluções Dinâmicas de Ambiente
*   **Nuven / Produção (Render):**
    Configuração para consumir a API REST remota:
    `'api_base_url' => 'https://gestao-epi-api.onrender.com/'`
*   **Ambiente Local (XAMPP):**
    Para desenvolvimento em rede local:
    `'api_base_url' => 'http://127.0.0.1/gestao_epi_api_7/'`
*   **CORS Gateway (`pages/api_proxy.php`):**
    Evita rejeições de Preflight CORS em requisições AJAX efetuadas pelo navegador do usuário. O script PHP atua como proxy Server-to-Server, repassando os cabeçalhos de autenticação `Authorization: Bearer <token>` de forma transparente.

### 3.2 Política de Retry e Timeouts
O `ApiService` implementa resiliência nativa contra oscilações de rede:
*   **Connect Timeout:** `3 segundos` (evita travamentos longos na tentativa de conexão).
*   **Response Timeout:** `10 segundos` (tempo máximo de espera pelo corpo da resposta).
*   **Política de Retry:** Em caso de erros temporários de gateway (`502 Bad Gateway`, `503 Service Unavailable`), o cliente realiza automaticamente até **1 tentativa adicional de retry** após 1 segundo.

---

## 4. CONTROLE DE ACESSO RBAC E CREDENCIAIS DE TESTE

O sistema possui um controle rigoroso de autorização baseado em papéis (**RBAC - Role-Based Access Control**). Cada tela verifica o perfil do usuário logado armazenado na sessão (`$_SESSION['usuario']['usu_perfil']`) antes de conceder acesso.

### 4.1 Matriz de Permissões de Acesso

| Perfil de Usuário | Código RBAC | Módulos Autorizados |
| :--- | :--- | :--- |
| **Administrador** | `admin` | Acesso total e irrestrito (Dashboard, Funcionários, EPIs, Entregas, Relatórios, Usuários, Auditoria e Configurações). |
| **Técnico SST** | `sst_tecnico` | Dashboard, Funcionários, EPIs (Catálogo/C.A.), Entregas & Devoluções e Relatórios. |
| **Almoxarife** | `almoxarife1` | Dashboard (sem métricas financeiras), Funcionários (Consulta), EPIs (Leitura/Estoque) e Entregas & Devoluções. |
| **Gestor de Contrato** | `gestor_contrato` | Dashboard (com relatórios de custo), Funcionários (Consulta), EPIs (Leitura), Entregas e Relatórios Gerenciais. |
| **RH Administrativo** | `rh_admin` | Funcionários (CRUD Completo de Colaboradores) e Configurações de Perfil. |

### 4.2 Credenciais Homologadas para Teste

| Perfil | Usuário (Login) | Senha Padrão | Objetivo do Teste |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin` | `admin123` | Validar controle de usuários, concessão de permissões e logs de auditoria JSON. |
| **Técnico SST** | `sst_tecnico` | `sst123` | Validar emissão de EPIs, controle de C.A. vencido e relatórios de conformidade NR-6. |
| **Almoxarife** | `almoxarife1` | `almox123` | Validar conferência de devoluções, recebimentos no estoque e entregas operacionais. |
| **Gestor** | `gestor_contrato` | `gestor123` | Validar demonstrativo de custos por departamento e consumo global de EPIs. |
| **RH Administrativo** | `rh_admin` | `rh123` | Validar cadastro de novos colaboradores, cargos e movimentação de departamento. |

---

## 5. MENU LATERAL E SUBMENUS EXPANSÍVEIS (SENIOR ERP STYLE)

**Arquivo:** [`components/sidebar.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/components/sidebar.php)

O menu lateral adota o padrão visual **Senior ERP Style com expansão retrátil por botão `+`**, combinando elegância moderna com acesso rápido em um clique:

*   **Tema Dark Slate:** Fundo escuro azul/grafite profundo (`#0b1120`) com tipografia clara e legível (`#94a3b8`).
*   **Submenus Expansíveis com Ícone `+`:** No menu **Funcionários**, o expansor lateral utiliza o ícone de adição **`+`** (`bi-plus-lg`), substituindo setas tradicionais. Ao expandir, o ícone transiciona para **`-`** (`bi-dash-lg`) e exibe as ações retráteis:
    *   👤 **Lista Funcionários** (`?acao=lista`): Redireciona/rola a página até a tabela principal e reseta filtros.
    *   ➕ **Novo Funcionário** (`?acao=novo`): Abre instantaneamente o modal Bootstrap `#modalCadastrar` para registro de novo colaborador.
    *   🔒 **Senha / PIN** (`?acao=pin`): Filtra e foca colaboradores para emissão/redefinição de PIN de assinatura eletrônica.
    *   ⚠️ **Pendências** (`?acao=pendencias`): Aplica filtro direto de colaboradores com pendências de assinatura de termo ou afastamento.
*   **Ícone Vetorial SVG de Capacete:** Ícone customizado de **Capacete Industrial EPI** no item *EPIs (Controle C.A.)*.
*   **Botão Flutuante Speed Dial (FAB `+`):** Na tela de funcionários, o botão circular flutuante no canto inferior direito permite abrir o menu empilhado de ações equivalentes aos submenus com animação suave.
*   **Botão Sair Fixo:** Posicionado na base da sidebar com destaque em vermelho para encerramento seguro de sessão.
*   **Renderização Dinâmica RBAC:** Filtra visualmente apenas os itens aos quais o usuário logado possui permissão explícita.

---

## 6. SISTEMA DE AUTOCOMPLETE AVANÇADO CLIENT-SIDE

O painel integra um mecanismo de busca por autocomplete em tempo real de altíssimo desempenho. Por ser **100% client-side** (filtrando dados pré-carregados em JSON na renderização inicial), não gera requisições HTTP adicionais durante a digitação.

### 6.1 Especificações Visuais e de Usabilidade

| Recurso | Detalhe da Implementação |
| :--- | :--- |
| **Gatilho de Ativação** | Dispara automaticamente ao digitar **2 ou mais caracteres**. |
| **Campos Filtrados** | Nome do colaborador, CPF, cargo, departamento e número de matrícula. |
| **Visual da Barra** | Ícone de lupa azul (`#3b82f6`) posicionado dentro do campo com botão `×` para limpar rapidamente. |
| **Dropdown Flutuante** | Renderizado com sombra profunda de 2 camadas (`box-shadow`), cantos arredondados (`12px`) e sobreposição segura (`z-index: 1050`). |
| **Cabeçalho Informativo** | Exibe a contagem de resultados encontrados e badges explicativas de navegação (`▲`, `▼`, `Enter`). |
| **Avatar Dinâmico** | Círculo 40×40px com cor gerada via algoritmo de *hash* a partir do nome e iniciais em caixa alta. |
| **Destaque de Texto (Highlight)** | Destaca em azul/negrito o trecho exato do texto correspondente à busca do usuário. |
| **Mascaramento LGPD** | CPF exibido formatado e mascarado em tom vermelho (`#e11d48`) para preservação de dados sensíveis. |
| **Navegação via Teclado** | Suporte às teclas `Seta para Baixo (↓)`, `Seta para Cima (↑)`, `Enter` (seleção) e `Escape` (fechamento). |

### 6.2 Módulos Integrados com Autocomplete

1.  **Funcionários ([`pages/funcionarios.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/funcionarios.php)):** Localização imediata de fichas de colaboradores por nome, CPF ou matrícula.
2.  **Catálogo de EPIs ([`pages/epis.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/epis.php)):** Busca por nome do equipamento, número de C.A., fabricante ou categoria.
3.  **Devoluções de EPIs ([`pages/devolucoes.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/devolucoes.php)):** Seleção ágil do colaborador para carregar os EPIs pendentes sob sua posse.
4.  **Filtro de Relatórios ([`pages/relatorios.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorios.php)):** Autocomplete no filtro de colaborador do relatório de Entregas Gerais, incluindo opção final para selecionar *"Todos os Funcionários"*.

---

## 7. MÓDULO DE RELATÓRIOS, EXPORTAÇÃO E AUDITORIA LGPD

O módulo de relatórios é composto por relatórios especializados que cobrem auditoria, custos, estoque e conformidade trabalhista:

1.  **Relatório Geral de Entregas ([`pages/relatorio_geral.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_geral.php)):** Listagem paginada com histórico completo de fornecimentos, colaborador, EPI, quantidade e data de entrega.
2.  **Relatório Financeiro de Custos ([`pages/relatorio_financeiro.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_financeiro.php)):** Demonstrativo mensal de investimentos em EPIs por departamento/centro de custo com valores formatados em **R$**.
3.  **Relatório de Consumo de EPIs ([`pages/relatorio_consumo_epi.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_consumo_epi.php)):** Quantitativo de itens entregues agrupados por tipo, categoria e fabricante no período selecionado.
4.  **Relatório de Validade de C.A. ([`pages/relatorio_validade_ca.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_validade_ca.php)):** Rastreabilidade rigorosa dos Certificados de Aprovação (NR-6), destacando EPIs com C.A. vencido ou a vencer nos próximos 30/60/90 dias.
5.  **Relatório de Auditoria de Logs ([`pages/relatorio_auditoria_logs.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_auditoria_logs.php)):** Histórico de operações realizadas no sistema com visualizador interativo de payloads em JSON.
6.  **Modelo de Impressão A4 Paisagem ([`pages/relatorio_auditoria_impressao.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/relatorio_auditoria_impressao.php)):** Layout profissional pré-formatado para impressão física ou geração de PDF oficial para fiscalizações do Trabalho/SST.

### 7.1 Conformidade e Governança LGPD nas Exportações
Toda exportação de relatórios (seja para formato **PDF** ou **CSV**) dispara automaticamente um log de auditoria em background para o endpoint `/logs/registrar-exportacao`. Esse registro grava o ID do operador, IP de origem, fuso horário (`America/Sao_Paulo`), tipo de relatório e parâmetros de filtro aplicados, garantindo total rastreabilidade.

---

## 8. OTIMIZAÇÕES DE DESEMPENHO E BANCO DE DADOS

Durante a evolução do projeto Web, foram aplicadas otimizações arquiteturais de alta performance:

*   **Eliminação do Problema de Consultas N+1:** Na listagem de histórico de entregas, o backend realizava uma consulta SQL individual para cada entrega a fim de buscar os itens (`ItemEntrega`). Foi implementada a busca em lote `findByEntregaIds()`, consolidando centenas de queries em apenas **2 consultas SQL otimizadas**. O tempo de resposta caiu de **15 a 45 segundos** para **menos de 0.1 segundo (5 milissegundos)**.
*   **Cache Busting de Assets CSS/JS:** O arquivo `components/header.php` carrega a folha de estilos com sufixo dinâmico de versão (`style.css?v=<?= time() ?>`), garantindo que atualizações visuais sejam refletidas instantaneamente sem retenção em cache do navegador.
*   **Invalidação Defensiva de Cache HTTP:** Cabeçalhos `Cache-Control: no-cache, no-store, must-revalidate` foram configurados globalmente para prevenir o retrabalho ou a exibição de dados desatualizados em navegadores corporativos.

---

## 9. HISTÓRICO DE ATUALIZAÇÕES E VERSÕES

### 📅 Versão 3.1.0 (11/09/2026) – Resiliência cURL, Suíte Completa de Relatórios PHP, Autenticação & Estabilização de UX
*   **Correção da Persistência de Sessão e Autenticação (`login.php`, `services/ApiService.php`, `components/header.php`):** Solucionado o problema de travamento e perda de sessão no login com credenciais homologadas (`admin` / `admin123`). O `ApiService` foi ajustado para restaurar a sessão via `@session_start()` pós-cURL, prevenindo perda de `$_SESSION` e loops de redirecionamento, além de sincronizar o `localStorage` (`token` e `usuario`) e logs de status no console.
*   **Política de Retry e Resiliência da API (`services/ApiService.php`):** Implementada política de retry automático contra erros de Gateway (`502 Bad Gateway`, `503 Service Unavailable`) com timeout adaptativo, garantindo reconexão transparente ao ambiente em nuvem (Render Free Tier) sem interrupção para o operador.
*   **Módulo de Relatórios Gerenciais Completo (`pages/`):** Consolidação dos 6 relatórios corporativos especializados em PHP puro (`relatorio_geral.php`, `relatorio_financeiro.php`, `relatorio_consumo_epi.php`, `relatorio_validade_ca.php`, `relatorio_auditoria_logs.php`, `relatorio_auditoria_impressao.php`), com suporte a exportação em PDF/CSV, máscaras LGPD e auditoria em background.
*   **Fluxos de Trabalho e Interfaces Dedicadas:** Adicionadas e homologadas as páginas de Aceite de Termos de Uso (`pages/aceitar-termos.php`), Ficha Completa do Colaborador (`pages/ficha_colaborador.php`) e Workflow em Etapas de Nova Entrega (`pages/nova_entrega.php`).
*   **Estabilização do Autocomplete Client-Side & Layout Senior ERP:** Refinamento dos scripts de autocomplete em todas as views do painel, garantindo renderização fluida sem sobreposição e navegação por teclado (setas/Enter).
*   **Conformidade Brasil Habilitada Globalmente:** Validação da obrigatoriedade do fuso horário `America/Sao_Paulo` (GMT-3) e formatação de moeda em Real Brasileiro (`BRL / R$`) em todos os componentes e templates.

### 📅 Versão 2.8.0 (10/09/2026) – Submenus Expansíveis de EPIs (Controle C.A.) & Speed Dial FAB
*   **Submenus Expansíveis de EPIs no Menu Lateral:** Implementados os 4 submenus retráteis com botão expansor `+` em [`components/sidebar.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/components/sidebar.php): *Lista de EPIs* (`?acao=lista`), *Novo EPI* (`?acao=novo`), *Controle C.A.* (`?acao=controle_ca`) e *Hist. de Preços* (`?acao=historico_precos`).
*   **Menu Flutuante Speed Dial (FAB) de EPIs:** Implementado o botão circular azul flutuante em [`pages/epis.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/epis.php) com transição `+` / `X` e menu empilhado com as 4 ações exatas da referência visual (*Hist. de Preços*, *Controle C.A.*, *Novo EPI*, *Lista de EPIs*).
*   **Modal de Histórico de Cotações de Preço:** Adicionado o modal `#modalHistoricoPrecos` para visualização e auditoria de preços homologados e origem de cotação dos EPIs.

### 📅 Versão 2.7.0 (10/09/2026) – Integração com gestao_epi_api_7, Telas Dedicadas de "Senha/PIN" e "Pendências", FAB e Navegação Mobile
*   **Integração Total com `gestao_epi_api_7`:** Conectado o painel aos contratos de dados da API v7 (`http://127.0.0.1/gestao_epi_api_7/`), consumindo endpoints de funcionários (`/funcionarios`), assinaturas (`/assinaturas`, `/assinaturas/redefinir`, `/assinaturas/bloquear/{id}`, `/assinaturas/desbloquear/{id}`) e métricas de pendências.
*   **Telas Dedicadas de "Senha/PIN" e "Pendências":** Implementadas as interfaces visuais dedicadas para os submenus "Funcionários com Senha Pendente" (cards brancos com borda arredondada, badges em laranja/âmbar, matrícula e CPF mascarado) e "Gerenciar Senha/PIN do Colaborador" (com campo de busca arredondado e botão integrado com ícone de usuário).
*   **Botão Flutuante (FAB) & Barra de Navegação Inferior (Mobile):** Adicionados o botão flutuante circular azul (FAB) com ícone de `+` no canto inferior direito e a barra de navegação inferior mobile (5 abas: `ENTREGAS`, `EPI'S`, `RELATÓRIOS`, `DASHBOARD`, `MAIS`).
*   **Correção de Navegação e Estabilização dos Submenus:** Corrigido o fluxo do menu lateral em [`components/sidebar.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/components/sidebar.php) e a desacoplagem de instâncias do Bootstrap Modal para eliminar travamentos e erros de JS (`TypeError: Cannot read properties of null`).
*   **Mapeamento de Status de Assinatura:** Incluída a propriedade `'assinatura_status'` e `'fun_matricula'` no objeto JavaScript `listaFuncionariosCadastrados` em [`pages/funcionarios.php`](file:///c:/Users/Ronaldo/Documents/ANTIGRAVITY/gestao_epi_web_2/pages/funcionarios.php), permitindo leitura exata do status do PIN em tempo real.

### 📅 Versão 2.6.0 (10/09/2026) – Importação de Schema v7 & Correção de Mapeamento SQL de Substituições
*   **Sincronização do Banco de Dados Schema v7:** Importado o arquivo `database_schema_v7.sql` (10/09/2026) no MySQL/MariaDB XAMPP (`database_schema`), atualizando a base com 91 entregas, 32 EPIs e 23 colaboradores.
*   **Correção de Coluna de Substituição SQL (`RelatoriosController.php`):** Corrigida a instrução SQL no relatório geral de fornecimento (`RelatoriosController.php`) para mapear `item_devolucao_vinculo_entrega_id AS entr_id_substituicao` e `item_devolucao_vinculo_item_id AS item_id_substituido`, eliminando o erro de execução `SQLSTATE[42S22]: Unknown column 'i.entr_id_substituicao'`.
*   **Ajuste de Compatibilidade MariaDB Windows:** Adicionada a instrução `ANSI_QUOTES` no cabeçalho do dump e comentadas as views duplicadas em maiúsculo para compatibilidade total no ambiente Windows (`lower_case_table_names=1`).

### 📅 Versão 2.5.0 (09/09/2026) – Submenus Expansíveis Senior ERP com Ícone `+`, Speed Dial FAB & Sincronização XAMPP
*   **Submenus Expansíveis com Botão `+`:** Implementado o menu retrátil no item **Funcionários** (`components/sidebar.php`) com o ícone expansor `+` (`bi-plus-lg`) substituindo setas, no padrão Senior ERP.
*   **Submenus Operacionais:** Integração das ações retráteis: *Lista Funcionários*, *Novo Funcionário* (acionamento direto do modal), *Senha / PIN* e *Pendências*.
*   **Menu Flutuante Speed Dial (FAB `+`):** Adicionado o botão flutuante expansível no canto inferior direito de Funcionários com animação e transição `+` / `X`.
*   **Suporte a Parâmetros de URL (`?acao=...`):** Acionamento automático das modais e filtros a partir de links de navegação externos ou menus da barra lateral.
*   **Sincronização em Tempo Real via Directory Junction:** Substituição de pasta estática do XAMPP por Directory Junction apontando diretamente para o ambiente de desenvolvimento workspace.

### 📅 Versão 2.4.0 (09/09/2026) – Autocomplete em Relatórios & Restauração do Menu Direct
*   **Autocomplete no Filtro de Relatórios:** Substituído o combo nativo pelo autocomplete interativo no relatório de Entregas Gerais (`pages/relatorios.php`).
*   **Reescrita da Tela de Devoluções:** Reestruturação total do arquivo `pages/devolucoes.php` em JavaScript Vanilla puro, eliminando tokens órfãos e aperfeiçoando a listagem de equipamentos sob posse do colaborador.
*   **Restauração da Sidebar Direct:** Ajustada a barra lateral (`components/sidebar.php`) para o layout direto e responsivo.

### 📅 Versão 2.3.0 (08/09/2026) – Autocomplete Avançado Multi-campo & Design System
*   **Mecanismo de Autocomplete Client-Side:** Implementação da busca inteligente com avatares dinâmicos, destaques de busca, navegação por teclado e mascaramento de CPF.
*   **Padronização do Design System:** Atualização das variáveis HSL em `assets/css/style.css` para suporte aprimorado a Light Mode e Dark Mode.

### 📅 Versão 3.0.0 (10/09/2026) – Unificação de Submenus Expansíveis & Speed Dial FAB Multi-Módulo
*   **Submenus Expansíveis Padronizados (`components/sidebar.php`):** Implementação e alinhamento dos submenus retráteis `+` / `-` para os três módulos corporativos:
    - **Funcionários:** `Lista Funcionários`, `Novo Funcionário`, `Senha / PIN` e `Pendências`.
    - **EPIs (Controle C.A.):** `Lista de EPIs`, `Novo EPI`, `Controle C.A.` e `Hist. de Preços`.
    - **Entregas & Devoluções:** `Histórico`, `Devolução` e `Nova Entrega`.
*   **Menu Flutuante Speed Dial FAB Sincronizado (`pages/epis.php`, `pages/entregas.php`, `pages/devolucoes.php`, `pages/nova_entrega.php`, `pages/funcionarios.php`):** Inclusão do botão flutuante circular azul (`+` / `X`) no canto inferior direito de todas as telas, com correspondência exata de ícones, rótulos e ações aos submenus da barra lateral.
*   **Posicionamento & Renderização Garantida:** Fixação via CSS `position: fixed !important; bottom: 28px !important; right: 28px !important; z-index: 999999 !important;` e injeção do HTML antes do encerramento da estrutura `#main-content`.

### 📅 Versão 2.9.0 (10/09/2026) – Submenus Expansíveis e Speed Dial (FAB) do Módulo Entregas & Devoluções
*   **Submenus Expansíveis (`components/sidebar.php`):** Implementação dos submenus retráteis `Histórico`, `Devolução` e `Nova Entrega` sob o menu *Entregas & Devoluções*, com botões de alternância `+` / `-`.
*   **Speed Dial (FAB) Flutuante (`pages/entregas.php`, `pages/devolucoes.php`, `pages/nova_entrega.php`):** Adição do menu flutuante circular azul com ícone `+` no canto inferior direito para acesso rápido às ações `Histórico`, `Devolução` e `Nova Entrega`.

### 📅 Versão 2.2.0 (04/09/2026) – Fuso Horário Oficial & Otimização de Performance cURL/SQL
*   **Padronização de Fuso Horário:** Garantida a inicialização global do fuso horário `America/Sao_Paulo` (GMT-3).
*   **Otimização N+1 SQL:** Redução drástica do tempo de carregamento de entregas de 45s para 5ms via busca em lote.
*   **Resiliência cURL:** Adicionados timeouts de conexão (3s) e resposta (10s) com retry automático.

### 📅 Versão 2.1.0 (13/08/2026) – Gateway CORS Proxy e Blindagem LGPD
*   **CORS Gateway Proxy (`pages/api_proxy.php`):** Desenvolvimento da ponte Server-to-Server para requisições AJAX.
*   **Auditoria de Exportação LGPD:** Registro em background de todas as emissões de relatórios.

---

## 10. REQUISITOS E GUIA DE EXECUÇÃO LOCAL

### 10.1 Requisitos de Ambiente
*   **Servidor Web:** Apache 2.4+ (XAMPP ou Docker) com módulo `mod_rewrite` ativo.
*   **Linguagem:** PHP 8.0 ou superior (com extensões `curl`, `json`, `mbstring` e `session` habilitadas).
*   **Navegadores Homologados:** Google Chrome, Microsoft Edge ou Mozilla Firefox (versões modernas).

### 10.2 Como Executar o Projeto no XAMPP

1.  Clone ou copie o repositório para o diretório `htdocs` do XAMPP:
    ```bash
    C:\xampp\htdocs\gestao_epi_web_2
    ```
2.  Certifique-se de que a API do ecossistema esteja em execução (localmente em `http://127.0.0.1/gestao_epi_api_7/` ou na nuvem em `https://gestao-epi-api.onrender.com/`).
3.  Inicie o servidor Apache via **XAMPP Control Panel**.
4.  Acesse a aplicação no navegador:
    ```text
    http://localhost/gestao_epi_web_2/
    ```
5.  Utilize qualquer um dos [Perfis de Teste Homologados](#42-credenciais-homologadas-para-teste) para navegar pelo painel.

---
*Desenvolvido e mantido pela equipe de Engenharia de Software da plataforma **Gestão EPI**.*

