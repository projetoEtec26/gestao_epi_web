# Banco de Dados & Governança LGPD — Gestão EPI

Este documento detalha o modelo relacional físico, o dicionário de dados das tabelas, as diretrizes de criptografia de dados sensíveis sob a **LGPD (Lei nº 13.709/2018)** e as regras de integridade referencial do banco de dados `db_gestao_epi`.

---

## 1. Visão Geral da Base de Dados

* **SGBD:** MySQL 8.0+ / MariaDB 10.3+
* **Encoding / Collation:** `utf8mb4 / utf8mb4_unicode_ci`
* **Mecanismo de Armazenamento:** `InnoDB` com suporte pleno a transações ACID e chaves estrangeiras com restrições `ON DELETE RESTRICT` e `ON UPDATE CASCADE`.

---

## 2. Dicionário de Tabelas Principais

### 2.1 Tabela `funcionarios` (Colaboradores Receptores de EPI)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :--- | :--- |
| `fun_id` | INT AUTO_INCREMENT | Não | Chave primária do colaborador. |
| `fun_nome` | VARCHAR(150) | Não | Nome completo do colaborador. |
| `fun_matricula` | VARCHAR(50) | Sim | Matrícula funcional ou registro de ponto. |
| `fun_cpf` | VARCHAR(20) | Sim | CPF no formato legível ou mascarado (`000.***.***-00`). |
| `fun_cpf_enc` | TEXT | Sim | CPF criptografado com chave AES-256-GCM (LGPD). |
| `fun_cpf_lookup` | VARCHAR(64) | Sim | Hash HMAC-SHA256 para busca indexada sem descriptografia. |
| `fun_cargo` | VARCHAR(100) | Sim | Cargo ocupado pelo colaborador. |
| `fun_departamento` | VARCHAR(100) | Sim | Setor / Departamento de alocação. |
| `car_id` | INT | Sim | Chave estrangeira referenciando tabela `cargos`. |
| `dep_id` | INT | Sim | Chave estrangeira referenciando tabela `departamentos`. |
| `fun_situacao` | ENUM | Não | `ATIVO`, `AFASTADO`, `DEMITIDO`, `FERIAS`. |
| `fun_ativo` | TINYINT(1) | Não | Flag booleana de atividade (`1` = Ativo, `0` = Inativo). |

### 2.2 Tabela `epis` (Catálogo de Equipamentos)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :--- | :--- |
| `epi_id` | INT AUTO_INCREMENT | Não | Chave primária do equipamento. |
| `epi_nome` | VARCHAR(150) | Não | Descrição comercial do EPI (ex: Luva Nitrílica). |
| `epi_ca` | VARCHAR(50) | Não | Número do Certificado de Aprovação emitido pelo MTE. |
| `epi_validade_ca` | DATE | Sim | Data de validade oficial do C.A. (NR-6). |
| `epi_fabricante` | VARCHAR(100) | Sim | Nome da empresa fabricante ou importadora. |
| `epi_numero_lote` | VARCHAR(100) | Sim | Lote padrão de fabricação para rastreabilidade obrigatória. |
| `epi_identificacao` | VARCHAR(100) | Sim | Código patrimonial ou código de barras. |
| `epi_estoque` | INT | Não | Saldo quantitativo disponível no almoxarifado. |
| `epi_preco_custo` | DECIMAL(10,2) | Não | Valor unitário de aquisição (em Real - R$). |
| `epi_validade_uso_dias`| INT | Sim | Prazo de vida útil máxima em dias após a entrega. |
| `epi_ativo` | TINYINT(1) | Não | `1` = Ativo no catálogo, `0` = Inativo/Descontinuado. |

### 2.3 Tabelas `entrega_epis` e `itens_entrega` (Fornecimento de EPI)

A estrutura de fornecimento adota relação Mestre-Detalhe (1:N):
* **`entrega_epis`**: Cabeçalho do fornecimento. Contém `entr_id`, `fun_id`, `entr_data_entrega`, `entr_status` (`FINALIZADA`, `CANCELADA`), `entr_tipo_assinatura` (`PIN`, `BIOMETRIA`), `entr_hash_assinatura` (Hash SHA-256 gerado no momento da assinatura para auditoria jurídica) e `entr_motivo`.
* **`itens_entrega`**: Itens individuais entregues. Contém `item_id`, `entr_id` (FK), `epi_id` (FK), `item_quantidade`, `item_tamanho`, `item_numero_lote` (**obrigatório**, preenchido em 100% dos registros), `item_epi_ca_snapshot` e `item_epi_validade_ca_snapshot`.

### 2.4 Tabela `assinatura_eletronica` (Credenciais de PIN)

| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :--- | :--- |
| `ass_id` | INT AUTO_INCREMENT | Não | Chave primária. |
| `fun_id` | INT | Não | ID do colaborador (relacionamento 1:1 único). |
| `ass_salt` | VARCHAR(64) | Não | Salt criptográfico aleatório em hexadecimal (16 bytes). |
| `ass_pin_hash` | VARCHAR(64) | Não | Hash SHA-256 gerado por `sha256(PIN + Salt)`. |
| `ass_status` | ENUM | Não | `ATIVO`, `BLOQUEADO`, `PENDENTE`, `INATIVO`. |
| `ass_tentativas_incorretas`| INT | Não | Contador de tentativas falhas (bloqueia ao atingir 3). |

### 2.5 Tabela `usuarios` (Operadores do Sistema)

| Coluna | Tipo | Nulo | Descrição / Regra de Negócio |
| :--- | :--- | :--- | :--- |
| `usu_id` | INT AUTO_INCREMENT | Não | Chave primária do operador. |
| `usu_login` | VARCHAR(50) | Não | Identificador de acesso único (ex: `admin`, `rh_admin`). |
| `usu_nome` | VARCHAR(150) | Não | Nome de exibição do operador. |
| `usu_email` | VARCHAR(150) | Sim | E-mail corporativo para recuperação de senha. |
| `usu_senha` | VARCHAR(255) | Não | Senha criptografada irreversível em `bcrypt` (`cost => 10`). |
| `usu_perfil` | ENUM | Não | `ADMINISTRADOR`, `TECNICO_SST`, `ALMOXARIFE`, `GESTOR_CONTRATO`, `RH_ADMINISTRATIVO`. |
| `usu_situacao` | ENUM | Não | `ATIVO`, `INATIVO`, `BLOQUEADO`. |
| `usu_tentativas_falha` | INT | Não | Contador de tentativas de login incorretas (bloqueio automático). |
| `usu_trocar_senha` | TINYINT(1) | Não | Flag para forçar troca de senha no primeiro login. |
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

## 4. Governança de Dumps e Isolamento de Credenciais (Release 8.4.9 Hardened)

1. **Remoção de Arquivos de Dumps do Git (Art. 46 da LGPD):**
   - Os arquivos de dump `database_backup.sql` e `database_schema_v7.sql` foram removidos do versionamento do Git e adicionados ao `.gitignore` e à lista de exclusão do deploy FTP (`.github/workflows/deploy.yml`), impedindo a exposição de CPFs, eSocial e hashes de operadores.
2. **Gerenciamento de Segredos de Conexão:**
   - O arquivo `config/api.php` não armazena credenciais em texto claro para produção.
   - Conexões em produção utilizam carregamento isolado fora da raiz pública (`/home/usuario_locaweb/secrets.php`) ou variáveis de ambiente.
3. **Firewall e Restrição de Acesso:**
   - A porta TCP 3306 do banco MySQL Cloud (`db_gestao_epi.mysql.dbaas.com.br`) é configurada para acesso restrito às sub-redes da aplicação, eliminando varreduras não autorizadas a partir da internet.

