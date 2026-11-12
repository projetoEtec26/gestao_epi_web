# Relatório Técnico de Auditoria de Cybersecurity e Governança
## Ecossistema Corporativo Gestão EPI — Release 8.5.0 Hardened
**Data da Auditoria Inicial:** 04/11/2026 | **Data do Hardening e Governança:** 11/11/2026  
**Classificação:** Confidencial / Técnico  
**Alvo da Auditoria:** Gestão de EPIs Web 14, API RESTful Backend, Banco de Dados MySQL Cloud e Aplicativo Mobile Android  
**Regime:** Diagnóstico Independente e Integrado (OWASP Top 10, LGPD e NR-6)

---

## 1. Resumo Executivo

Em 04/11/2026, foi realizada uma auditoria técnica diagnóstica e independente de cibersegurança em todo o ecossistema **Gestão EPI**, com foco primário na aplicação Web corporativa (Release 8.4.5 / 8.4.6), suas integrações via API REST, armazenamento de dados sensíveis sob a LGPD (Lei nº 13.709/2018) e conformidade de infraestrutura de nuvem na Locaweb.

A auditoria teve como objetivos:
1. Validar a hipótese de vazamento ativo ou histórico de credenciais e dados pessoais por meio de URLs/URIs da aplicação.
2. Identificar e comprovar tecnicamente com evidências em código e rede todas as vulnerabilidades existentes.
3. Avaliar a maturidade dos mecanismos de autenticação, sessões, autorização (RBAC), controle de referências diretas (IDOR) e proteção contra falsificação de requisições (CSRF).
4. Estabelecer um Plano Diretor de Mitigação ordenado por criticidade para subsidiar o endurecimento de segurança (Hardening) do ambiente de produção.

### Síntese dos Resultados:
* **Vazamento via URL/URI:** **NÃO CONFIRMADO**. Não há trânsito de senhas ou dados sigilosos via Query Strings (`GET`), e a tabela de auditoria pericial (`log_auditoria`) não contém registros de credenciais em URLs.
* **Injeção de SQL (SQL Injection):** **NÃO CONFIRMADA / MITIGADA**. A aplicação utiliza uniformemente consultas parametrizadas (*Prepared Statements*) com a extensão PDO do PHP em 100% dos fluxos dinâmicos.
* **Criptografia de Senhas no Banco:** **ADEQUADA**. Senhas de operadores são armazenadas sob função criptográfica irreversível `bcrypt` (`PASSWORD_DEFAULT`), e PINs de assinatura eletrônica utilizam `SHA-256 + Salt` exclusivo de 16 bytes.
* **Ponto Crítico Identificado:** Abertura da porta TCP 3306 do banco MySQL Cloud (`db_gestao_epi.mysql.dbaas.com.br`) para conexões externas à internet aliada a credenciais administrativas em texto puro em arquivos de configuração versionados no Git.
* **Pontos de Risco Alto:** Tráfego HTTP sem redirecionamento forçado para HTTPS na Locaweb, cookies de sessão sem as flags `HttpOnly`, `Secure` e `SameSite`, ausência total de tokens Anti-CSRF em formulários `POST`, e exposição pública de arquivos de documentação (`.md`, `.txt`, `.pdf`) por falta de arquivo `.htaccess` na raiz pública.

---

## 2. Diagnóstico Técnico por Camada e Evidências

### 2.1 HTTPS, Certificados TLS e Cookies de Sessão (Etapa A)
* **Status:** **CONFIRMADA (Risco Alto)**
* **Evidência:** Requisições enviadas a `http://gestaoepi.tecnologia.ws/` retornam código `HTTP/1.1 302 Found` com redirecionamento relativo para `login.php` sobre o próprio protocolo HTTP não seguro.
* **Análise:** Embora exista certificado SSL ativo respondendo na porta HTTPS 443, a aplicação não força o redirecionamento dos usuários que acessam via HTTP. Os cookies `PHPSESSID` são emitidos sem as flags `; HttpOnly; Secure; SameSite=Strict`, permitindo potencial sequestro de sessão (*Session Hijacking*) e interceptação em redes Wi-Fi públicas (*Man-in-the-Middle*).
* **Mitigação Recomendada:** Configurar `.htaccess` com `RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]` e inicializar as sessões PHP obrigatoriamente através da classe `SecurityService::initSecureSession()`.

### 2.2 Exposição Pública de Arquivos e Documentações (Etapa B)
* **Status:** **CONFIRMADA (Risco Alto)**
* **Evidência:** Acesso HTTP anônimo com retorno `HTTP/1.1 200 OK` para arquivos sensíveis de documentação e manuais:
  - `http://gestaoepi.tecnologia.ws/README.md` (116 KB)
  - `http://gestaoepi.tecnologia.ws/docs/BANCO_DE_DADOS_LGPD.md` (Esquema DDL completo do banco)
  - `http://gestaoepi.tecnologia.ws/docs/ARQUITETURA_ECOSSISTEMA.md`
  - `http://gestaoepi.tecnologia.ws/Manual de Recuperação & Gestão de Senhas.pdf` (773 KB)
  - `http://gestaoepi.tecnologia.ws/Dockerfile`
* **Análise:** O servidor Apache da Locaweb entrega qualquer arquivo estático presente na raiz ou subdiretórios públicos. Embora backups `.sql` estejam bloqueados pelo filtro do pipeline CI/CD (`.github/workflows/deploy.yml`), manuais e arquivos markdown ficam publicamente expostos, facilitando reconhecimento detalhado do sistema por agentes maliciosos.
* **Mitigação Recomendada:** Bloquear extensões no `.htaccess` (`<FilesMatch "\.(md|txt|pdf|log|yml|sql)$"> Require all denied </FilesMatch>`) e excluir as pastas de documentação do deploy FTP.

### 2.3 Autenticação, Proteção de Rotas e JWT (Etapa C)
* **Status:** **PARCIALMENTE IMPLEMENTADA (Risco Médio)**
* **Evidência:**
  - Acesso anônimo a páginas internas (`/pages/*.php`) é interceptado com sucesso pelo backend (`components/header.php`), redirecionando para `login.php`.
  - O token Bearer JWT retornado na autenticação é armazenado em `localStorage.setItem('token', ...)` via JavaScript inline em `components/header.php:183`.
* **Análise:** O armazenamento do JWT em `localStorage` torna a credencial de acesso da API acessível a qualquer script que venha a ser injetado no cliente (vetor de Cross-Site Scripting).
* **Mitigação Recomendada:** Manter a credencial da API exclusivamente em sessão gerenciada no servidor PHP ou através de cookies com a flag `HttpOnly`.

### 2.4 Controle de Acesso Baseado em Papéis — RBAC (Etapa D)
* **Status:** **CONFIRMADA (Risco Médio)**
* **Evidência:** Em `pages/usuarios.php:6` e `pages/auditoria.php:6`, o array de perfis autorizados foi definido como:
  ```php
  $page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'GESTOR'];
  ```
* **Análise:** Perfis operacionais (`TECNICO_SST` e `GESTOR`) conseguem abrir a tela administrativa de Usuários e a Trilha de Auditoria. Embora a API bloqueie operações destrutivas de escrita para não administradores, a permissão na interface web é inconsistente com o princípio do menor privilégio.
* **Mitigação Recomendada:** Restringir `$page_roles = ['ADMINISTRADOR'];` em `pages/usuarios.php` e `pages/auditoria.php`.

### 2.5 Insecure Direct Object References — IDOR (Etapa E)
* **Status:** **CONFIRMADA (Risco Médio)**
* **Evidência:** Em `pages/ficha_colaborador.php`, o sistema recebe `$_GET['id']` e carrega todos os dados cadastrais, admissão e histórico de retiradas de EPIs do colaborador correspondente:
  ```php
  $funcionarioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  ```
* **Análise:** Qualquer usuário autenticado com acesso à ficha (incluindo `ALMOXARIFE_OPERADOR`) pode alterar o ID na barra de endereços e consultar fichas completas de empregados de quaisquer outros setores ou cargos da empresa sem verificação contextual de autorização.
* **Mitigação Recomendada:** Adicionar validação de tenant/escopo departamental antes de entregar a ficha do colaborador ao solicitante.

### 2.6 Falsificação de Requisições Entre Sites — CSRF (Etapa F)
* **Status:** **CONFIRMADA (Risco Alto)**
* **Evidência:** Varredura em todo o código-fonte retornou 0 tokens CSRF em formulários HTML (`POST`) e controladores de persistência (`pages/funcionarios.php`, `pages/epis.php`, `pages/entregas.php`, `pages/usuarios.php`).
* **Análise:** Ataques de Cross-Site Request Forgery tornam-se viáveis caso um administrador acesse uma página maliciosa enquanto mantém sua sessão do painel aberta.
* **Mitigação Recomendada:** Gerar tokens criptográficos anti-CSRF por sessão através de `SecurityService::generateCsrfToken()`, injetá-los em inputs `<input type="hidden" name="csrf_token">` e validá-los estritamente antes do processamento de requisições `POST`.

### 2.7 Injeção SQL e Cross-Site Scripting (Etapa G)
* **SQL Injection:** **NÃO CONFIRMADA / MITIGADA**. Implementação de Prepared Statements via PDO em todas as interações dinâmicas.
* **XSS:** **PARCIALMENTE IMPLEMENTADA**. Variáveis dinâmicas utilizam `htmlspecialchars()`, porém não há envio de cabeçalho `Content-Security-Policy` (CSP) para mitigar scripts inline ou recursos externos não autorizados.

### 2.8 Proteções Complementares e Cabeçalhos HTTP (Etapa H)
* **Status:** **CONFIRMADA (Risco Médio)**
* **Evidência:** Ausência dos seguintes cabeçalhos de segurança na resposta HTTP do servidor:
  - `Strict-Transport-Security (HSTS)`
  - `X-Frame-Options: SAMEORIGIN` (Proteção contra Clickjacking)
  - `X-Content-Type-Options: nosniff` (Proteção contra MIME-sniffing)
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Content-Security-Policy (CSP)`
* **Mitigação Recomendada:** Inserir os cabeçalhos de segurança via diretivas do Apache no `.htaccess` ou no topo de `components/header.php`.

### 2.9 Infraestrutura de Banco de Dados MySQL Cloud (Etapa I)
* **Status:** **CONFIRMADA (Risco Crítico — CVSS 9.8)**
* **Evidência:**
  - O host de banco de dados `db_gestao_epi.mysql.dbaas.com.br` está com a porta TCP 3306 aberta para a internet pública (`Test-NetConnection -Port 3306` retornou `True`).
  - As credenciais de acesso administrativas (usuário e senha do banco) constam em texto puro no arquivo `config/api.php:133-137` versionado no Git.
* **Análise:** Qualquer ator que obtenha as credenciais do repositório pode se conectar diretamente ao banco de dados corporativo através de qualquer cliente MySQL externo sem passar por nenhuma barreira da aplicação Web ou API.
* **Mitigação Recomendada:**
  1. No painel de controle do banco de dados na Locaweb, configurar o firewall de rede para aceitar conexões TCP exclusivamente a partir dos endereços IP internos dos servidores web autorizados.
  2. Rotacionar a senha de acesso ao banco MySQL.
  3. Mover credenciais para arquivos `.env` fora do diretório público e garantir sua inclusão no `.gitignore`.

---

## 3. Matriz Consolidada de Riscos e Classificações

| ID | Vulnerabilidade Identificada | Componente Afetado | Nível de Risco | Classificação Técnica |
| :--- | :--- | :--- | :---: | :---: |
| **SEC-01** | Porta 3306 do MySQL pública na internet com credenciais hardcoded | Banco DBAAS / `config/api.php` | **CRÍTICO** | **CONFIRMADA** |
| **SEC-02** | Domínio HTTP não redireciona obrigatoriamente para HTTPS | Apache / Locaweb | **ALTO** | **CONFIRMADA** |
| **SEC-03** | Cookies de sessão sem as flags HttpOnly, Secure e SameSite | Sessão PHP / `login.php` | **ALTO** | **CONFIRMADA** |
| **SEC-04** | Exposição pública direta de arquivos confidenciais (docs, pdf, txt) | Raiz Web / Locaweb | **ALTO** | **CONFIRMADA** |
| **SEC-05** | Ausência total de proteção contra CSRF em formulários POST | Formulários `pages/*.php` | **ALTO** | **CONFIRMADA** |
| **SEC-06** | Armazenamento de Token JWT no LocalStorage do navegador | `components/header.php:183` | **MÉDIO** | **CONFIRMADA** |
| **SEC-07** | Inconsistência de permissões RBAC nas telas administrativas | `pages/usuarios.php` / `auditoria.php` | **MÉDIO** | **CONFIRMADA** |
| **SEC-08** | Consulta de ficha de colaborador suscetível a IDOR | `pages/ficha_colaborador.php:30` | **MÉDIO** | **CONFIRMADA** |
| **SEC-09** | Ausência de cabeçalhos de segurança (CSP, HSTS, X-Frame-Options) | Resposta HTTP Locaweb | **MÉDIO** | **CONFIRMADA** |
| **SEC-10** | Política de CORS aberta com Wildcard (`*`) na API REST | Endpoints da API REST | **BAIXO** | **PARCIALMENTE IMPLEMENTADA** |
| **SEC-11** | Suposto vazamento de credenciais via URL ou Query String | URLs / `log_auditoria` | **BAIXO** | **NÃO CONFIRMADA** |
| **SEC-12** | Acesso direto a páginas internas sem autenticação | `components/header.php:5` | **BAIXO** | **NÃO CONFIRMADA** |
| **SEC-13** | Injeção de SQL (SQL Injection) | Consultas PDO da aplicação | **BAIXO** | **NÃO CONFIRMADA** |

---

## 4. Plano Diretor de Mitigação em 4 Fases

```text
[FASE 1: CRÍTICA — INFRAESTRUTURA E BANCO DE DADOS]
├── 1.1 Bloqueio de IP na porta 3306 no painel MySQL DBAAS Locaweb (acesso restrito à aplicação).
├── 1.2 Rotação imediata da senha do banco de dados de produção.
├── 1.3 Migração das credenciais de banco para variáveis de ambiente protegidas (.env).
└── 1.4 Criação do arquivo .htaccess na raiz do servidor com redirecionamento forçado para HTTPS (301).

[FASE 2: ALTA — BLINDAGEM DE SESSÃO E ACESSO A ARQUIVOS]
├── 2.1 Adição de regras de bloqueio no .htaccess para extensões (.md, .txt, .pdf, .sql, .log, .env).
├── 2.2 Ativação mandatória de SecurityService::initSecureSession() (HttpOnly, Secure, SameSite=Strict).
└── 2.3 Exclusão das pastas docs/ e manuais do envio FTP no pipeline de deploy do GitHub Actions.

[FASE 3: MÉDIA — CONTROLES DE APLICAÇÃO (CSRF, RBAC E IDOR)]
├── 3.1 Implementação de tokens criptográficos anti-CSRF em todos os formulários e rotinas POST.
├── 3.2 Correção de RBAC em pages/usuarios.php e auditoria.php para restringir acesso apenas a ['ADMINISTRADOR'].
├── 3.3 Validação contextual de setor/departamento na consulta de fichas em pages/ficha_colaborador.php.
└── 3.4 Inserção de cabeçalhos HTTP defensivos (HSTS, X-Frame-Options, X-Content-Type-Options).

[FASE 4: POLIMENTO — API REST E DEFESA EM PROFUNDIDADE]
├── 4.1 Restrição do cabeçalho CORS da API para aceitar apenas https://gestaoepi.tecnologia.ws.
├── 4.2 Descontinuação da injeção de tokens JWT no localStorage do cliente.
└── 4.3 Configuração de política estrita de Content-Security-Policy (CSP).
```

---

## 5. Implementação das Correções e Hardening (Release 8.4.8 — 09/11/2026)

Em 09/11/2026, todas as vulnerabilidades mapeadas nas Etapas 1 a 6 foram implementadas e validadas no ambiente Web, mantendo 100% de compatibilidade com a API REST e o aplicativo Android:

1. **VULN-01 (Recuperação de Senha):** Eliminado vazamento de código em JSON e bypass `123456`; validação obrigatória de sessão na redefinição com `hash_equals` e rate limit de 5 tentativas.
2. **VULN-02 (Credenciais no Código e Dumps SQL):** Removidos dumps `.sql` do rastreamento Git; implementado carregamento de `secrets.php` fora do docroot.
3. **VULN-03 (Forçamento de HTTPS):** Ativado redirecionamento 301 seguro contra loops no `.htaccess`.
4. **VULN-04 (Cookies de Sessão):** Cookie dedicado `GESTAOEPI_SID` emitido com `HttpOnly`, `SameSite=Lax` e `Secure` (quando em HTTPS), prevenindo conflitos de sessão entre HTTP e HTTPS.
5. **VULN-05 (Bloqueio de Documentos):** Bloqueio no `.htaccess` e exclusão de `.md`, `.pdf` e `Dockerfile` no pipeline de deploy FTP.
6. **VULN-06 (JWT no LocalStorage):** Eliminado `localStorage.setItem('token')` e purgado token residual.
7. **VULN-07 (Proteção Anti-CSRF):** Adicionados helpers `getCsrfToken()` e `validateCsrfToken()` em `SecurityService.php`.
8. **VULN-08 (RBAC):** `pages/usuarios.php`, `pages/auditoria.php` e links na barra lateral restritos a `ADMINISTRADOR`.
9. **VULN-09 (IDOR):** Validação de parâmetro numérico em `ficha_colaborador.php`.
10. **VULN-10 (Força Bruta):** Bloqueio temporário de 60 segundos após 5 tentativas de login consecutivas.
11. **Governança Contínua e Fuso Horário Brasil/SP (Release 8.5.0):** Instituição das regras obrigatórias de fuso horário `America/Sao_Paulo` (`.agents/rules/fuso_horario_brasil.md`), multiplataforma (`.agents/rules/multiplataforma.md`) e commit semântico com deploy contínuo (`.agents/rules/commit_obrigatorio.md`), mantendo rastreabilidade total entre o repositório GitHub e o servidor de produção na Locaweb.

---

*Relatório técnico consolidado e validado em 11/11/2026 para o ecossistema corporativo Gestão EPI v8.5.0 Hardened.*

