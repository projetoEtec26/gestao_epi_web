---
trigger: always_on
---

# Regra: Funcionamento Obrigatório em Web, Celular e Tablet

Toda funcionalidade do Gestão EPI Web **deve funcionar igualmente** em:

| Plataforma | Largura de referência | Entrada |
|---|---|---|
| **Web Desktop** (Chrome, Edge, Firefox) | ≥ 1200px | Mouse e teclado |
| **Tablet** | 768px – 1199px | Toque |
| **Celular** | < 768px | Toque |

Uma alteração só é considerada concluída quando funciona nas **três** plataformas.

## Obrigatório em toda alteração

1. **Eventos de interação**: tratar mouse (`mousedown`/`click`), toque (`touchend` com `passive: false`) e teclado (`Enter`/`Espaço`). Nunca depender só de toque ou só de mouse.
2. **Componentes nativos do navegador** (`input[type=date]`, `select`, etc.): usar os componentes customizados do projeto (ex.: calendário Material Design em `assets/js/main.js`) para manter o mesmo visual e comportamento em todas as plataformas.
3. **Modais e sobreposições**: respeitar a hierarquia de `z-index` de `assets/css/style.css` (modal Bootstrap = 10050; sobreposições acima do modal ≥ 100000). Elementos abertos de dentro de um modal não podem ser anexados ao `.modal-content` (overflow corta) e devem ficar dentro do focus-trap do Bootstrap.
4. **Layout responsivo**: usar grid/utilitários do Bootstrap 5 e as media queries existentes (`max-width: 1199.98px`). Áreas de toque com no mínimo 44×44px. Sem rolagem horizontal na página.
5. **Modo escuro**: toda nova tela/componente deve ter estilos `html.dark-mode` / `body.dark-mode`.
6. **Tabelas**: devem ter rolagem horizontal (`table-responsive`) ou layout em cartões no celular.

## Toda alteração atualiza as três plataformas

Qualquer alteração ou modificação (nova funcionalidade, correção, ajuste visual, texto, regra de negócio) **deve ser aplicada e publicada para Web, celular e tablet ao mesmo tempo**:

1. **Nunca corrigir só uma plataforma.** Se o problema foi relatado em uma (ex.: só na Web), a correção deve ser feita no código compartilhado e conferida nas outras duas. Proibido criar versões separadas de telas por dispositivo.
2. **Arquivos compartilhados primeiro**: preferir alterar `assets/css/style.css`, `assets/js/main.js` e `components/*.php`, que são usados por todas as plataformas.
3. **Cache**: CSS e JS são carregados com `?v=<?= time() ?>` (`components/header.php` e `components/footer.php`). Manter esse padrão em qualquer novo arquivo estático para que celulares e tablets recebam a versão nova sem limpar o cache.
4. **Publicação obrigatória**: ao concluir e validar a alteração no XAMPP, fazer `git commit` e `git push` na branch `main` para disparar o deploy FTP (GitHub Actions → Locaweb). Uma alteração que ficou só no XAMPP **não está entregue**.
5. **Confirmação**: após o deploy, conferir em `http://gestaoepi.tecnologia.ws/` e informar ao usuário o que foi publicado. Se o push/deploy não puder ser feito, avisar explicitamente que a produção ainda não foi atualizada.

## Verificação antes de entregar

- Testar no XAMPP (`http://localhost/OLD/gestao_epi_web_14/`) em Desktop e no modo dispositivo do DevTools (celular ~390px e tablet ~820px).
- Publicar em produção conforme a seção acima.
- Se não for possível testar alguma plataforma, informar explicitamente ao usuário.
