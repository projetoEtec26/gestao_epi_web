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

### 1.2 Regra de Compatibilidade Multiplataforma (Web Desktop, Tablet e Celular)

Toda funcionalidade do Gestão EPI Web opera de forma idêntica em três formatos de tela:

| Plataforma | Largura de Referência | Entrada Primária | Adaptação de Interface |
| :--- | :--- | :--- | :--- |
| **Web Desktop** | $\ge 1200\text{px}$ | Mouse e teclado | Grid multi-colunas, tabelas completas, modais centralizados e sidebar expandida. |
| **Tablet** | $768\text{px} – 1199\text{px}$ | Toque e teclado virtual | Grid compactado, tabelas com scroll horizontal suave (`table-responsive`) e botões com área de toque mínima de $44\times 44\text{px}$. |
| **Celular** | $< 768\text{px}$ | Toque | Layout verticalizado em cartões (*cards*), sidebar recolhível em *offcanvas*, modais full-screen ou rolagem vertical sem transbordo horizontal. |

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
* **Gestão de Equipamentos:** Cadastro com Fabricante, C.A. (MTE), Validade do C.A., Estoque, Preço de Custo, Unidade, Lote de Fábrica e Vida Útil em dias.
* **Reajuste em Lote:** Modal administrativo para aplicação de percentual de acréscimo ou desconto nos valores de custo dos EPIs.
* **Controle Preventivo de Validade:** Badges coloridos indicando C.A. regular (verde), a vencer em até 90 dias (amarelo) e vencido (vermelho).

### 3.4 Histórico de Entregas e Recibo Eletrônico (`pages/entregas.php`)
* **Histórico Transacional:** Filtro por status (`FINALIZADA`, `CANCELADA`), período de datas e colaborador.
* **Recibo / Ficha de Entrega Eletrônica de EPI:**
  - Modal estilizado com cabeçalho de auditoria e status "ASSINADO DIGITALMENTE".
  - Dados do receptor: Nome completo, CPF mascarado, Cargo e Departamento.
  - Grid de equipamentos: Nome, Fabricante, C.A., Validade do C.A., Quantidade, Tamanho e **Lote Obrigatório**.
  - **Garantia de Lote:** O campo Lote sob nenhuma hipótese exibe `---` ou vazio, utilizando resolução em cascata (`item_numero_lote` -> `epi_numero_lote` -> `epi_identificacao` -> `LT-ANO-COD`).
  - Termo de Compromisso de guarda e conservação da NR-6.
  - Trilha judicial de auditoria: Hash SHA-256 da assinatura, tipo de assinatura (PIN), data e hora legal.
  - Botão de "Imprimir Termo" com formatação otimizada para folhas físicas A4.

### 3.5 Workflow de Nova Entrega (`pages/nova_entrega.php`)
* **Fluxo em Etapas:**
  1. *Identificação do Colaborador:* Seleção via autocomplete de alta precisão.
  2. *Seleção de Equipamentos:* Adição de itens ao carrinho com verificação de estoque e validade de C.A.
  3. *Preenchimento Obrigatório de Lote:* O campo `#input-lote-item` é automaticamente preenchido com o lote do equipamento ou gerado no padrão institucional.
  4. *Coleta de Assinatura Eletrônica:* Digitação do PIN de 6 dígitos do colaborador com validação criptográfica síncrona.

### 3.6 Devoluções de EPIs (`pages/devolucoes.php`)
* **Inspeção de Retorno:** Seleção do colaborador e listagem dos equipamentos sob sua posse.
* **Motivo da Devolução:** Término de contrato, substituição periódica, dano/desgaste ou transferência.
* **Atualização de Estoque:** Retorno imediato das quantidades ao saldo disponível no almoxarifado quando o item estiver em condições de reuso.

### 3.7 Ficha Individual do Colaborador (`pages/ficha_colaborador.php`)
* Dossiê completo do colaborador com dados cadastrais, histórico cronológico de todos os EPIs recebidos, termos assinados, devoluções registradas e equipamentos atualmente sob sua custódia.

### 3.8 Central de Relatórios Gerenciais (`pages/relatorios.php`)
* **Relatório Geral de Entregas:** Histórico analítico com paginação e filtros combinados.
* **Relatório Financeiro de Custos (`pages/relatorio_financeiro.php`):** Investimento por centro de custo/departamento.
* **Relatório de Consumo de EPIs (`pages/relatorio_consumo_epi.php`):** Saídas quantitativas por período e equipamento.
* **Relatório de Validade de C.A. (`pages/relatorio_validade_ca.php`):** Rastreabilidade de conformidade com a NR-6.
* **Modelo Oficial de Impressão A4 Paisagem (`pages/relatorio_auditoria_impressao.php`):** Folha padronizada para fiscalização trabalhista.

### 3.9 Auditoria de Logs e Segurança (`pages/auditoria.php` e `pages/usuarios.php`)
* **Trilha de Auditoria Judicial:** Monitoramento pericial de todas as ações de usuários com visualizador de JSON.
* **Gestão de Operadores (RBAC):** Criação de usuários, concessão de perfis e imposição de troca de senha no primeiro acesso.
