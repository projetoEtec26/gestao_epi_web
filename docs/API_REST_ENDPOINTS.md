# Especificação Completa dos Endpoints — Gestão EPI API

Este documento descreve detalhadamente a arquitetura de rotas, contratos de entrada e saída, padrões de cabeçalhos e códigos de resposta da API RESTful do ecossistema **Gestão EPI** ([projetoEtec26/gestao_epi_api](https://github.com/projetoEtec26/gestao_epi_api)).

---

## 1. Padrões de Comunicação e Cabeçalhos

* **Base URL Nuvem:** `https://gestao-epi-api.onrender.com/`
* **Base URL Local:** `http://127.0.0.1/gestao_epi_api/`
* **Formato de Dados:** `application/json; charset=utf-8`
* **Fuso Horário Padrão:** `America/Sao_Paulo` (GMT-3)

### Cabeçalhos Padrão da Requisição

| Cabeçalho | Tipo | Obrigatório | Descrição |
| :--- | :--- | :--- | :--- |
| `Content-Type` | String | Sim (POST/PUT) | Deve ser `application/json`. |
| `Authorization` | String | Em rotas protegidas | Formato `Bearer <token_jwt>`. |
| `X-Device-Info` | String | Recomendado | Identificação do cliente (ex: `Web (Chrome/Win11)` ou `Mobile (Android 14)`). |

---

## 2. Matriz de Endpoints

### 2.1 Autenticação e Gestão de Acesso (`/auth`)

| Método | Rota | Autenticado | Descrição |
| :--- | :--- | :--- | :--- |
| `POST` | `/auth/login` | Não | Autentica o operador com `usu_login` e `senha`. Retorna token JWT e perfil. |
| `GET` | `/auth/me` | Sim | Retorna os dados do operador atualmente autenticado pelo token JWT. |
| `POST` | `/auth/trocar-senha` | Sim | Altera a senha do operador logado (exige senha atual e nova senha). |
| `POST` | `/auth/solicitar-recuperacao`| Não | Solicita código de recuperação de senha por e-mail/identificação. |
| `POST` | `/auth/validar-codigo` | Não | Valida o código numérico temporário de recuperação. |
| `POST` | `/auth/redefinir-senha` | Não | Redefine a senha esquecida mediante token/código de recuperação validado. |

#### Exemplo de Resposta de Login (`POST /auth/login`):
```json
{
  "success": true,
  "message": "Autenticação realizada com sucesso.",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "usuario": {
    "usu_id": 1,
    "usu_login": "admin",
    "usu_nome": "Administrador do Sistema",
    "usu_perfil": "ADMINISTRADOR",
    "exige_troca_senha": false
  }
}
```

---

### 2.2 Gestão de Colaboradores (`/funcionarios`)

| Método | Rota | Perfil Mínimo | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/funcionarios` | Consulta | Retorna a lista de colaboradores ativos com status de assinatura e departamento. |
| `GET` | `/funcionarios/{id}` | Consulta | Retorna os detalhes da ficha de um colaborador específico. |
| `POST` | `/funcionarios` | RH / Admin | Cadastra novo colaborador (gera automaticamente PIN padrão `123456`). |
| `PUT` | `/funcionarios/{id}` | RH / Admin | Atualiza dados cadastrais, cargo, departamento ou status. |
| `DELETE` | `/funcionarios/{id}` | Admin | Desativa logicamente o colaborador (`fun_ativo = 0`). |
| `POST` | `/funcionarios/{id}/pin/redefinir` | SST / Admin | Redefine o PIN de assinatura eletrônica do colaborador para `123456`. |
| `POST` | `/funcionarios/{id}/pin/validar` | Operação | Valida se o PIN digitado (6 dígitos) corresponde ao hash do colaborador. |

---

### 2.3 Catálogo de EPIs e Controle de C.A. (`/epis`)

| Método | Rota | Perfil Mínimo | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/epis` | Consulta | Retorna catálogo completo de EPIs, estoque, C.A., validade e lote padrão. |
| `GET` | `/epis/{id}` | Consulta | Retorna dados detalhados e histórico de preços do EPI. |
| `POST` | `/epis` | SST / Admin | Cadastra novo EPI com controle de validade de C.A. e vida útil em dias. |
| `PUT` | `/epis/{id}` | SST / Admin | Atualiza informações, fabricante, estoque e lote do equipamento. |
| `POST` | `/epis/reajustar-precos` | Admin / Gestor | Aplica percentual de reajuste em lote nos preços de custo dos EPIs. |

---

### 2.4 Movimentações de Entrega e Devolução (`/entregas` e `/devolucoes`)

| Método | Rota | Perfil Mínimo | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/entregas` | Operação | Lista todas as entregas realizadas com colaborador, status e quantidade. |
| `GET` | `/entregas/{id}` | Operação | Retorna itens, termo de compromisso e dados de auditoria judicial da entrega. |
| `POST` | `/entregas` | Almoxarife / SST | Registra nova entrega com assinatura eletrônica por PIN e **lote obrigatório**. |
| `GET` | `/devolucoes` | Operação | Lista devoluções de EPIs realizadas e motivo de retorno (desgaste, troca, etc.). |
| `POST` | `/devolucoes` | Almoxarife / SST | Registra devolução de item, atualiza estoque e estorna pendência de posse. |

#### Estrutura do Payload de Entrega (`POST /entregas`):
```json
{
  "fun_id": 14,
  "pin_assinatura": "123456",
  "dispositivo": "Web (Chrome 128 / Windows 11)",
  "itens": [
    {
      "epi_id": 13,
      "quantidade": 1,
      "tamanho": "M",
      "lote": "LT-2026-013",
      "motivo": "ADMISSAO"
    }
  ]
}
```

---

### 2.5 Central de Relatórios e Auditoria (`/relatorios` e `/auditoria`)

| Método | Rota | Descrição |
| :--- | :--- | :--- |
| `GET` | `/relatorios/geral` | Relatório consolidado de fornecimentos de EPIs por período e filtros. |
| `GET` | `/relatorios/financeiro` | Demonstrativo de custos e consumo por centro de custo/departamento. |
| `GET` | `/relatorios/consumo` | Métricas quantitativas de saída e movimentação por EPI. |
| `GET` | `/relatorios/validade-ca` | Rastreio preventivo de vencimento de C.A. (NR-6) em 30, 60 e 90 dias. |
| `GET` | `/auditoria/logs` | Trilha de auditoria judicial completa com paginação e busca por operador. |
| `POST` | `/logs/registrar-exportacao`| Registra no log de auditoria qualquer emissão ou download de PDF/CSV. |
