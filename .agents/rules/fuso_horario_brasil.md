# Regra: Fuso Horário Oficial Brasil / São Paulo (America/Sao_Paulo)

Todo o ecossistema **Gestão EPI** (Painel Web Administrativo, API REST, Banco de Dados MySQL, Relatórios e App Android) deve operar **exclusivamente** no fuso horário oficial de **Brasília / São Paulo (`America/Sao_Paulo` — GMT-3 / UTC-3)**.

## 1. Obrigatoriedade no Backend PHP e Servidor Apache
* Todos os arquivos de entrada, serviços (`SecurityService`, `ApiService`), cabeçalhos (`components/header.php`, `config/api.php`) e relatórios de impressão/PDF devem declarar explicitamente no início da execução:
  ```php
  date_default_timezone_set('America/Sao_Paulo');
  ```
* O arquivo `.htaccess` deve manter a diretiva `SetEnv TZ America/Sao_Paulo` para garantir que o ambiente Apache/PHP na nuvem (Locaweb) e local (XAMPP) operem no mesmo fuso horário.
* Toda instanciação de objetos `DateTime` ou `DateTimeImmutable` no PHP deve utilizar explicitamente `new DateTimeZone('America/Sao_Paulo')` e converter eventuais timestamps UTC (`Z` / `+00:00`) para `America/Sao_Paulo` antes de exibir ou persistir.

## 2. Obrigatoriedade no Banco de Dados (MySQL) e Auditoria de Logs
* As conexões PDO com o MySQL devem configurar a sessão para `time_zone = '-03:00'`.
* Todos os registros na tabela `log_auditoria` (`log_datahora`), entregas (`entr_data_entrega`), devoluções (`item_data_devolucao`), assinaturas eletrônicas e bloqueios devem ser gravados e exibidos no horário de **São Paulo / Brasil (`America/Sao_Paulo`)**.
* É proibido exibir ou gravar datas/horários em UTC ou em fuso horário estrangeiro.

## 3. Obrigatoriedade no Frontend (JavaScript) e Formatação Visual
* Formato obrigatório de exibição em telas, modais e relatórios PDF/CSV:
  - **Data:** `DD/MM/AAAA` (ex.: `11/11/2026`)
  - **Data e Hora (Logs e Auditoria):** `DD/MM/AAAA HH:MM:SS` (ex.: `11/11/2026 18:57:01`)
  - **Data e Hora (Operacional):** `DD/MM/AAAA HH:MM` (ex.: `11/11/2026 18:57`)
* Qualquer formatação de data/hora via JavaScript (`toLocaleString`, `toLocaleDateString`, `toLocaleTimeString`) deve informar o idioma `'pt-BR'` e o fuso `timeZone: 'America/Sao_Paulo'`.
