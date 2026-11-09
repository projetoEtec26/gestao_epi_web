# Banco de Dados & Governança LGPD — Ecossistema Gestão EPI

Este documento detalha o dicionário de dados físico, a modelagem relacional, as diretrizes de criptografia e as regras de conformidade com a **LGPD (Lei Geral de Proteção de Dados - Lei nº 13.709/2018)** e a **NR-6 (Ministério do Trabalho e Emprego)** do ecossistema **Gestão EPI**.

---

## 1. Visão Geral da Modelagem e Arquitetura

O banco de dados corporativo `db_gestao_epi` foi projetado para alta concorrência e integridade referencial estrita:
* **Mecanismo de Armazenamento:** `InnoDB` em 100% das tabelas para suporte a transações ACID e integridade referencial com chaves estrangeiras (`FOREIGN KEY`).
* **Conjunto de Caracteres (Charset):** `utf8mb4` com Collation `utf8mb4_unicode_ci` (suporte a acentuação e caracteres especiais em língua portuguesa).
* **Fuso Horário Padrão:** `America/Sao_Paulo` (GMT-3) gravado em todas as colunas de data e hora.

---

## 2. Dicionário de Dados das Tabelas Principais

### 2.1 Tabela `tb_funcionario` (Colaboradores)

Armazena os dados cadastrais e funcionais dos colaboradores da empresa:

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :---: | :--- |
| `fun_id` | INT AUTO_INCREMENT | Não | Chave primária do colaborador. |
| `fun_nome` | VARCHAR(120) | Não | Nome completo do colaborador. |
| `fun_matricula` | VARCHAR(30) | Não | Matrícula funcional única na empresa. |
| `dep_id` | INT | Não | Chave estrangeira para a tabela `departamentos`. |
| `car_id` | INT | Não | Chave estrangeira para a tabela `cargos`. |
| `fun_cpf_enc` | VARCHAR(255) | Não | **CPF Criptografado** via AES-256-GCM sob a LGPD. |
| `fun_cpf_lookup` | VARCHAR(64) | Não | Hash indexado HMAC-SHA256 para busca exata de CPF sem descriptografia. |
| `fun_esocial_enc` | VARCHAR(255) | Sim | Matrícula eSocial criptografada via AES-256-GCM. |
| `fun_data_admissao` | DATE | Não | Data oficial de admissão do colaborador. |
| `fun_status` | ENUM | Não | Situação: `ATIVO`, `INATIVO`, `DEMITIDO`, `AFASTADO`. |
| `fun_ativo` | TINYINT(1) | Não | Flag booleana de exclusão lógica (1 = Ativo, 0 = Inativo). |

### 2.2 Tabela `assinatura_eletronica` (Credenciais de Assinatura)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :---: | :--- |
| `ass_id` | INT AUTO_INCREMENT | Não | Chave primária da credencial de assinatura. |
| `fun_id` | INT | Não | Chave estrangeira referenciando `tb_funcionario(fun_id)`. |
| `hash_pin` | CHAR(64) | Não | Hash SHA-256 do PIN (`sha256(PIN + Salt)`). |
| `salt` | CHAR(32) | Não | Salt criptográfico aleatório de 16 bytes em hexadecimal. |
| `status` | ENUM | Não | `ATIVO`, `PENDENTE`, `BLOQUEADO`. Padrão: `ATIVO` com PIN inicial `123456`. |
| `tentativas_incorretas`| INT | Não | Contador para bloqueio preventivo após erros consecutivos. |
| `atualizado_em` | DATETIME | Não | Carimbo da última alteração de senha/PIN. |

### 2.3 Tabela `epis` (Catálogo de Equipamentos)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :---: | :--- |
| `epi_id` | INT AUTO_INCREMENT | Não | Chave primária do equipamento. |
| `epi_nome` | VARCHAR(120) | Não | Nome comercial do EPI (ex: Calçado de Segurança com Biqueira). |
| `epi_categoria` | VARCHAR(50) | Não | Categoria (Proteção Auditiva, Visual, Cabeça, Respiratória, Calçado, etc.). |
| `epi_fabricante` | VARCHAR(100) | Não | Razão social ou marca do fabricante homologado. |
| `epi_ca` | VARCHAR(20) | Sim | **Número do Certificado de Aprovação (C.A.)** emitido pelo MTE. |
| `epi_validade_ca` | DATE | Sim | Data de validade oficial do Certificado de Aprovação. |
| `epi_numero_lote` | VARCHAR(50) | Sim | Número do lote de fabricação do equipamento no almoxarifado. |
| `epi_vida_util_dias`| INT | Não | Período máximo de uso recomendado em dias corridos antes da troca. |
| `epi_estoque_atual` | INT | Não | Saldo físico atual em estoque no almoxarifado. |
| `epi_estoque_minimo`| INT | Não | Ponto de pedido para alertas automáticos de reposição. |
| `epi_valor` | DECIMAL(10,2) | Não | Valor unitário em Reais (`R$`). |
| `epi_status` | ENUM | Não | `ATIVO`, `OBSOLETO`, `INATIVO`. |

### 2.4 Tabelas de Movimentação: `entregas` e `itens_entrega`

* **`entregas`:** Registra o evento de fornecimento de equipamentos:
  - `entr_id`: Chave primária.
  - `fun_id`: Colaborador receptor.
  - `usu_id`: Operador responsável pelo atendimento no sistema.
  - `entr_data_entrega`: Carimbo da entrega no fuso `America/Sao_Paulo`.
  - `entr_status`: `FINALIZADA`, `CANCELADA`.
  - `entr_assinatura_hash`: Hash pericial comprovando a validação do PIN no momento da entrega.
* **`itens_entrega`:** Detalhamento individual dos equipamentos entregues:
  - `item_id`: Chave primária do item.
  - `entr_id`: Chave estrangeira para `entregas`.
  - `epi_id`: Chave estrangeira para `epis`.
  - `item_quantidade`: Quantidade fornecida.
  - `item_numero_lote`: **Lote obrigatório (NR-6)** registrado para o item.
  - `item_epi_ca_snapshot`: Número do C.A. vigente no ato da entrega.
  - `item_epi_validade_ca_snapshot`: Validade do C.A. no ato da entrega.
  - `item_status`: `EM_USO`, `DEVOLVIDO`, `DESCARTADO`.

### 2.5 Tabela `usuarios` (Operadores do Sistema)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :---: | :--- |
| `usu_id` | INT AUTO_INCREMENT | Não | Chave primária do operador. |
| `usu_login` | VARCHAR(50) | Não | Nome de usuário único para autenticação. |
| `usu_senha` | VARCHAR(255) | Não | Senha criptografada irreversivelmente via **Bcrypt** (`PASSWORD_BCRYPT`). |
| `usu_nome` | VARCHAR(100) | Não | Nome completo do operador. |
| `usu_perfil` | ENUM | Não | Perfil RBAC: `ADMINISTRADOR`, `TECNICO_SST`, `ALMOXARIFE`, `GESTOR_CONTRATO`, `RH_ADMINISTRATIVO`. |
| `usu_status` | ENUM | Não | `ATIVO`, `INATIVO`, `BLOQUEADO`. |
| `usu_tentativas_falha` | INT | Não | Contador para bloqueio progressivo de força bruta em `login.php`. |
| `usu_ultimo_login` | DATETIME | Sim | Carimbo do último acesso no fuso `America/Sao_Paulo`. |

### 2.6 Tabelas Estruturais `departamentos` e `cargos`

* **`departamentos`**: Setores corporativos oficiais (`dep_id`, `dep_nome`, `dep_codigo`, `dep_ativo`). Setores homologados: `Administrativo`, `Engenharia`, `Gestão`, `Produção`, `Produção - Web`, `RH`.
* **`cargos`**: Atribuições funcionais dos colaboradores (`car_id`, `car_nome`, `car_descricao`, `dep_id` [FK], `car_ativo`).

### 2.7 Tabela `log_auditoria` (Trilha de Auditoria Judicial)

Armazena de forma imutável todos os eventos críticos do sistema:
* `log_id`: Chave primária sequencial.
* `usu_id`: ID do operador responsável (ou `NULL` para ações automáticas de sistema).
* `log_acao`: Operação realizada (`LOGIN`, `CADASTRO_EPI`, `ENTREGA_EPI`, `DEVOLUCAO_EPI`, `EXPORTACAO_RELATORIO`, `REDEFINICAO_PIN`).
* `log_tabela`: Tabela afetada.
* `log_registro_id`: ID do registro modificado.
* `log_data_hora`: Carimbo de data/hora no fuso `America/Sao_Paulo`.
* `log_ip`: Endereço IP do operador.
* `log_dispositivo`: Informações do dispositivo e navegador coletadas via cabeçalho `X-Device-Info`.
* `log_detalhes`: Detalhamento em formato JSON estruturado com o estado anterior e novo do registro.

---

## 3. Conformidade e Segurança LGPD

1. **Criptografia Simétrica de Alto Desempenho (AES-256-GCM):**
   - Chave de segurança: 256 bits derivada de variável de ambiente segura.
   - Vetor de Inicialização (IV): 12 bytes gerados por `random_bytes(12)` por operação.
   - Tag de Autenticação GCM: 16 bytes que validam que a mensagem criptografada não foi adulterada em repouso.
2. **Mascaramento Preventivo (Privacy by Default):**
   - Usuários com perfis operacionais visualizam dados sensíveis mascarados, minimizando o risco de vazamento visual.
3. **Imutabilidade dos Logs de Auditoria:**
   - A tabela `log_auditoria` não possui rotas nem comandos de `UPDATE` ou `DELETE`, atuando como registro de auditoria pericial para comprovação de entrega e conformidade em processos judiciais trabalhistas.

---

## 4. Governança de Dumps, Isolamento e CI/CD (Release 8.4.8 / 8.4.9)

1. **Remoção de Arquivos de Dumps do Git (Art. 46 da LGPD):**
   - Os arquivos de dump `database_backup.sql` e `database_schema_v7.sql` foram removidos do versionamento do Git e adicionados ao `.gitignore`, impedindo a exposição de CPFs, eSocial e hashes de operadores.
2. **Gerenciamento de Segredos de Conexão:**
   - O arquivo `config/api.php` não armazena credenciais em texto claro para produção.
   - Conexões em produção utilizam carregamento isolado fora da raiz pública (`/home/usuario_locaweb/secrets.php`) ou variáveis de ambiente.
3. **Firewall e Restrição de Acesso:**
   - A porta TCP 3306 do banco MySQL Cloud é configurada para acesso restrito às sub-redes da aplicação, eliminando varreduras não autorizadas a partir da internet.
4. **Governança de Integridade via Commit Obrigatório:**
   - A política de trabalho com árvore limpa (`working tree clean`) impede que arquivos de dump locais ou backups temporários de dados reais permaneçam na estrutura do projeto ou sejam expostos inadvertidamente.
