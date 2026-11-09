# Painel Web Administrativo — Gestão EPI

Este documento descreve detalhadamente a arquitetura de interface, os componentes de software, as regras de negócio de cada tela e o design system do **Painel Web Administrativo** do ecossistema corporativo **Gestão EPI** ([projetoEtec26/gestao_epi_web](https://github.com/projetoEtec26/gestao_epi_web)).

---

## 1. Visão Geral e Diretrizes Multiplataforma

O Painel Web Administrativo é o centro de controle unificado para gestão de segurança do trabalho, fiscalização e conformidade trabalhista (NR-6) e proteção de dados (LGPD). 

### 1.1 Diretrizes Oficiais do Brasil
* **Idioma:** Português do Brasil (`pt-BR`).
* **Moeda:** Real Brasileiro (`BRL / R$`), formatado via função global `formatarValorMonetario()`.
* **Formato de Datas:** `DD/MM/AAAA` (com suporte a exibição de horário no padrão 24h `HH:mm:ss`).
* **Fuso Horário:** `America/Sao_Paulo` (GMT-3), inicializado no topo de todos os scripts e templates.

### 1.2 Regra de Compatibilidade Multiplataforma Estrita (Web Desktop, Tablet e Celular)

Toda funcionalidade do Gestão EPI Web opera de forma idêntica e sem perda de recursos em três formatos de tela:

| Plataforma | Largura de Referência | Entrada Primária | Adaptação de Interface |
| :--- | :--- | :--- | :--- |
| **Web Desktop** | $\ge 1200\text{px}$ | Mouse e teclado | Grid multi-colunas, tabelas completas, modais centralizados e sidebar expandida. |
| **Tablet** | $768\text{px} – 1199\text{px}$ | Toque e teclado virtual | Grid compactado, tabelas com scroll horizontal suave (`table-responsive`) e botões com área de toque mínima de $44\times 44\text{px}$. |
| **Celular** | $< 768\text{px}$ | Toque | Layout verticalizado em cartões (*cards*), sidebar recolhível em *offcanvas*, modais full-screen ou rolagem vertical sem transbordo horizontal. |

#### Critérios Técnicos Mandatórios em Toda Alteração de Interface:
1. **Eventos Híbridos de Interação:** Tratar eventos de mouse (`mousedown`/`click`), toque (`touchend` com `passive: false`) e teclado (`Enter`/`Espaço`). É estritamente proibido depender apenas de mouse ou apenas de toque.
2. **Componentes Customizados Padronizados:** Uso dos componentes personalizados do projeto (ex.: calendário Material Design em `assets/js/main.js` e seletores customizados), garantindo consistência visual em qualquer navegador móvel ou desktop.
3. **Hierarquia de Modais e Z-Index:** O modal Bootstrap base opera em `z-index: 10050`. Elementos flutuantes, autocompletes e sobreposições atuam em `z-index: 999999 !important`. Elementos filhos não podem sofrer corte por `overflow: hidden` do `.modal-content`.
4. **Layout Responsivo e Áreas de Toque:** Grid e utilitários do Bootstrap 5 sob `@media (max-width: 1199.98px)`. Dimensão mínima de toque de $44\times 44\text{px}$ para evitar toques acidentais em smartphones.
5. **Cobertura Universal de Modo Escuro:** Toda nova tela ou componente deve implementar estilos para `html.dark-mode` / `body.dark-mode`.
6. **Tabelas Fluídas:** Uso mandatório de contêineres `.table-responsive` ou renderização alternativa em formato de cartões (*cards*) em resoluções móveis.

---

## 2. Design System e Identidade Visual (`assets/css/style.css`)

O sistema visual utiliza **Vanilla CSS moderno baseado em tokens HSL**, garantindo alta legibilidade e transição suave entre temas:

1. **Light Mode (Tema Claro):**
   - Fundo principal: `--bg-primary: #f8fafc;` (Slate 50).
   - Superfície de cartões: `--bg-surface: #ffffff;` com sombras suaves de elevação.
   - Texto principal: `--text-main: #0f172a;` (Slate 900, contraste AAA).
   - Cor primária institucional: `--primary: #2563eb;` (Azul Royal).

2. **Dark Mode (Tema Escuro):**
   - Ativado via classe `html.dark-mode` / `body.dark-mode` com persistência em `localStorage`.
   - Fundo principal: `--bg-dark-primary: #090d16;` (Dark Navy profundo).
   - Superfície de cartões: `--bg-dark-surface: #111827;` (Slate escuro).
   - Texto principal: `--text-dark-main: #f1f5f9;` (Branco gelo nítido).
   - Realce de contraste: `.highlight-match` em tom primário de alto contraste contra fundos escuros.

3. **Componentes e Efeitos Especiais:**
   - **Glassmorphism:** Utilizado em modais, barras de ferramentas e cards com `backdrop-filter: blur(12px)`.
   - **Submenus Expansíveis Senior ERP Style:** Expansores retráteis `+` / `-` na barra lateral com animação de altura.
   - **Speed Dial FAB Flutuante:** Botão circular flutuante posicionado em `fixed; bottom: 28px; right: 28px; z-index: 999999;` presente nas telas operacionais para acesso imediato às ações em telas móveis e tablets.

---

## 3. Módulos e Especificação das Telas (`pages/`)

### 3.1 Painel Gerencial & Indicadores (`pages/dashboard.php`)
* **KPIs Principais:** Total de EPIs entregues, equipamentos em uso ativo, colaboradores cadastrados e taxa de conformidade.
* **Alertas Críticos no Banner:**
  - EPIs com C.A. vencido no catálogo ou em uso (Prioridade Máxima / Vermelho).
  - Colaboradores com EPIs próximos da vida útil (Troca programada em 30 dias).
  - Colaboradores sem PIN de assinatura cadastrado ou com pendência de termo.
* **Gráficos Dinâmicos (Chart.js):**
  - Entregas mensais x devoluções.
  - Distribuição de custos por departamento.
  - Top 5 EPIs mais fornecidos.

### 3.2 Gestão de Funcionários (`pages/funcionarios.php`)
* **CRUD Completo:** Cadastro, visualização de ficha, edição e desligamento.
* **Autocomplete Multi-Campo:** Busca instantânea client-side por Nome, CPF, Cargo, Departamento ou Matrícula.
* **Geração Automática de PIN:** Novos colaboradores recebem automaticamente o PIN padrão `123456` ativo.
* **Visualização com Mascaramento LGPD:** CPF exibido como `000.***.***-00` por padrão.

### 3.3 Catálogo de EPIs e Controle de C.A. (`pages/epis.php`)
* **Gestão de Equipamentos:** Cadastro com foto, unidade, fabricante, vida útil em dias e controle de estoque.
* **Monitoramento de C.A.:** Badges dinâmicos com contadores automáticos para C.A. Válido (Verde), A Vencer em 30/60/90 dias (Laranja), Vencido (Vermelho) e Isento (Azul).
* **Histórico de Preços:** Linha do tempo com variações de valores homologados em Reais (`R$`).

### 3.4 Emissão de Entregas e Assinatura Eletrônica (`pages/nova_entrega.php`)
* **Fluxo em Etapas:**
  1. Identificação do colaborador receptor.
  2. Seleção de equipamentos com validação em tempo real de saldo e validade de C.A.
  3. Preenchimento compulsório do **número de lote** (NR-6).
  4. Coleta do PIN de 6 dígitos com validação criptográfica SHA-256 e gravação na trilha de auditoria.

### 3.5 Histórico Geral de Entregas e Recibos (`pages/entregas.php`)
* **Rastreabilidade Completa:** Lista com filtros por período, setor e colaborador.
* **Modal de Recibo de Entrega Eletrônica:**
  - Exibição de dados completos: Nome, CPF mascarado, Cargo, Setor e identificação visual de Lote em 100% dos itens.
  - Emissão de termo oficial com hash criptográfico de validação.

### 3.6 Controle de Devoluções e Baixas (`pages/devolucoes.php`)
* **Encerramento de Custódia:** Identificação dos EPIs sob posse do colaborador.
* **Classificação de Destino:** Retorno ao almoxarifado (apto para reuso) ou descarte definitivo (fim de vida útil ou danificado).

### 3.7 Ficha Completa do Colaborador (`pages/ficha_colaborador.php`)
* **Dossiê Funcional:** Visão 360° com dados cadastrais, histórico de entregas com C.A. e Lote, substituições e devoluções.

### 3.8 Central de Relatórios Gerenciais (`pages/relatorios.php`)
* **6 Modalidades Especializadas:** Relatório Geral de Fornecimento, Relatório Financeiro de Custos, Relatório de Consumo por Equipamento, Validade de C.A., Logs de Auditoria e Impressão Oficial A4.
* **Exportação Segura:** Exportação em CSV com codificação UTF-8 BOM e geração de PDF com registro automático no log de auditoria LGPD.

### 3.9 Usuários e Permissões (`pages/usuarios.php`)
* **Acesso Restrito:** Exclusivo para o perfil `ADMINISTRADOR`.
* **Governança RBAC:** Atribuição de permissões operacionais, redefinição de senhas e bloqueio preventivo de acessos.

### 3.10 Auditoria de Logs (`pages/auditoria.php`)
* **Acesso Restrito:** Exclusivo para o perfil `ADMINISTRADOR`.
* **Trilha Pericial:** Rastreabilidade completa com decodificador JSON estruturado, IP, data/hora no fuso `America/Sao_Paulo` e dispositivo do operador.

### 3.11 Gateway Server-to-Server e Tratamento de Erros (`pages/api_proxy.php`, `403.php`, `404.php`)
* **CORS Proxy Gateway (`api_proxy.php`):** Ponte Server-to-Server que viabiliza requisições assíncronas seguras do navegador para a API REST sem bloqueios de política de mesma origem (CORS).
* **Tratamento de Exceções HTTP:** Telas estilizadas de bloqueio por autorização insuficiente (`403 Acesso Negado`) e inexistência de recurso (`404 Não Encontrado`).

---

## 4. Matriz Consolidada das 18 Páginas e Componentes

| Arquivo | Título da Página / Rota | Perfis Autorizados (RBAC) | Funcionalidade Principal |
| :--- | :--- | :--- | :--- |
| `pages/dashboard.php` | Painel Principal | Todos | KPIs, Alertas SST, Gráficos e Central de Pendências |
| `pages/funcionarios.php` | Gestão de Funcionários | RH / SST / Admin | CRUD de colaboradores, PIN padrão `123456`, busca multi-campo |
| `pages/epis.php` | EPIs (Controle C.A.) | Almoxarife / SST / Admin | Catálogo de EPIs, controle de C.A., vida útil e reajuste em lote |
| `pages/entregas.php` | Histórico Geral de Entregas | Almoxarife / SST / Admin | Histórico de entregas, recibo assinado digitalmente e **lote obrigatório** |
| `pages/nova_entrega.php` | Entregas & Devoluções | Almoxarife / SST / Admin | Emissão de EPIs em etapas, conferência de estoque e assinatura por PIN |
| `pages/devolucoes.php` | Devolução de EPI | Almoxarife / SST / Admin | Baixa de custódia, estorno de estoque e inspeção de descarte |
| `pages/ficha_colaborador.php` | Ficha do Colaborador | SST / RH / Admin | Dossiê individual de equipamentos, termos e histórico trabalhista |
| `pages/relatorios.php` | Central de Relatórios | SST / Gestor / Admin | Central consolidada com 6 modalidades de relatórios gerenciais |
| `pages/relatorio_geral.php` | Relatório Geral de Fornecimento | SST / Gestor / Admin | Listagem analítica com paginação e exportação CSV/PDF |
| `pages/relatorio_financeiro.php` | Relatório Financeiro de Custos | Gestor / Admin | Demonstrativo de investimentos por departamento e gráficos de custo |
| `pages/relatorio_consumo_epi.php`| Relatório de Consumo por EPI | Almoxarife / SST / Admin | Saídas quantitativas por equipamento com auto-impressão oficial |
| `pages/relatorio_validade_ca.php`| Relatório de Validade de C.A. | SST / Admin | Monitoramento preventivo de vencimento de C.A. (NR-6) |
| `pages/relatorio_auditoria_logs.php` | Relatório de Logs de Auditoria | SST / Admin | Auditoria de acessos e modificações estruturais com JSON |
| `pages/relatorio_auditoria_impressao.php` | Impressão Oficial A4 | SST / Admin | Formatação em modelo oficial de folha para fiscalizações periciais |
| `pages/usuarios.php` | Usuários e Permissões | Exclusivo `ADMINISTRADOR` | Criação de operadores, perfis RBAC e redefinição de acesso |
| `pages/auditoria.php` | Auditoria de Logs | Exclusivo `ADMINISTRADOR` | Trilha de auditoria pericial judicial com decodificador JSON |
| `pages/configuracoes.php` | Configurações do Sistema | Todos | Gestão de senha, tema Dark/Light e diagnóstico de conexão |
| `pages/aceitar-termos.php` | Termos e Políticas LGPD | Todos | Validação obrigatória de termos de uso e privacidade |
| `pages/api_proxy.php` | Gateway CORS Server-to-Server | Todos (Autenticado) | Proxy seguro para chamadas AJAX sem bloqueio de navegador |
| `pages/403.php` | Acesso Não Autorizado | Público / Erro | Bloqueio amigável de RBAC com redirecionamento |
| `pages/404.php` | Página Não Encontrada | Público / Erro | Tratamento visual de recursos inexistentes |

---

## 5. Diretrizes de Segurança, Sessões e RBAC (Release 8.4.8 Hardened)

1. **Blindagem de Sessões (`SecurityService::initSecureSession`):**
   - Configuração de cookies de sessão nativos com `HttpOnly`, `SameSite=Lax` e `Secure` (sob HTTPS).
   - Prevenção ativa de roubo de sessão (*Session Hijacking*) e Cross-Site Scripting (XSS).
2. **Proteção Contra Força Bruta (`login.php`):**
   - Monitoramento contínuo de tentativas de autenticação inválidas.
   - Bloqueio temporário progressivo de 60 segundos após 5 erros consecutivos.
3. **Assistente de Recuperação Seguro (`recuperar-senha.php`):**
   - Omissão de códigos na resposta JSON e eliminação de senhas ou códigos universais.
   - Expiração estrita de 15 minutos e validação de sessão obrigatória para redefinição.
4. **Remoção de JWT no LocalStorage (`components/header.php`):**
   - Descontinuação do armazenamento do token Bearer JWT no cliente; todas as chamadas client-side trafegam através do gateway server-side `pages/api_proxy.php` que consome a sessão do servidor.
5. **Governança RBAC e Menu Sidebar:**
   - As páginas `pages/usuarios.php` e `pages/auditoria.php` operam com acesso restrito com exclusividade ao perfil `ADMINISTRADOR`, sincronizadas dinamicamente com os itens do menu lateral em `components/sidebar.php`.

---

## 6. Governança de Commits e Entrega Contínua (Release 8.4.9)

1. **Validação Pré-Commit Mandatória:**
   - Todo ajuste em telas, scripts JS, folhas de estilo CSS ou componentes PHP deve ser verificado com o linter PHP (`php -l`) e validado visualmente no XAMPP local.
   - O teste deve abranger Desktop ($\ge 1200\text{px}$) e modo responsivo do DevTools (Celular ~390px e Tablet ~820px).
2. **Commit e Publicação Obrigatória (`git commit` + `git push`):**
   - Toda alteração deve receber commit semântico imediato e ser enviada à branch `main` do GitHub (`https://github.com/projetoEtec26/gestao_epi_web.git`).
   - A publicação aciona o pipeline GitHub Actions (`deploy.yml`) para sincronização imediata na Locaweb (`http://gestaoepi.tecnologia.ws/`).
   - Uma modificação concluída apenas no ambiente local não é considerada entregue.
