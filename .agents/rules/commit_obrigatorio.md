---
trigger: always_on
---

# Regra: Commit e Publicação Obrigatória a Cada Alteração ou Modificação

Toda vez que qualquer alteração, correção, ajuste, adição ou modificação de código, estilo, configuração ou documentação for realizada no projeto:

## 1. Commit Obrigatório
* Ao concluir a alteração e validar a sintaxe e o funcionamento local no XAMPP, deve-se **obrigatoriamente realizar o `git add` dos arquivos pertinentes e o `git commit`**.
* Utilizar mensagens semânticas padronizadas (Conventional Commits):
  - `feat:` para novas funcionalidades ou componentes.
  - `fix:` para correções de bugs ou falhas de segurança.
  - `docs:` para atualizações de documentação e manuais.
  - `style:` para ajustes visuais ou de formatação CSS/layout.
  - `refactor:` para refatoração de código sem alteração de regra de negócio.

## 2. Publicação para o GitHub (`git push`)
* Logo após a criação do commit, deve-se enviar as alterações para o repositório remoto no GitHub (`https://github.com/projetoEtec26/gestao_epi_web.git` na branch `main`).
* A publicação no GitHub é indispensável para acionar o pipeline CI/CD via GitHub Actions (`.github/workflows/deploy.yml`) e atualizar o ambiente de produção na Locaweb.

## 3. Diretriz de Conclusão de Tarefa
* **Nenhuma alteração fica pendente:** Nenhuma modificação pode permanecer sem commit ou solta na árvore de trabalho local (`working tree dirty`).
* **Comunicação Transparente:** Ao final de cada atendimento, informar expressamente ao usuário o hash e a mensagem do commit realizado. Caso o push remoto enfrente qualquer restrição de permissão de acesso no GitHub, orientar imediatamente o usuário sobre a liberação de credenciais.
