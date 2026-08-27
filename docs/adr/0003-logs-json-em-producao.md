# ADR 0003: Logs JSON em Producao

## Status

Proposto

## Contexto

Por padrao, o Laravel escreve logs em formato de texto multi-linha no arquivo `storage/logs/laravel.log`. Em producao, esses logs precisam ser coletados por ferramentas de observabilidade (ELK, Loki, Datadog, CloudWatch) e parseados automaticamente. Logs textuais dificultam esse parse, aumentam o custo de indexacao e perdem contexto estruturado.

## Decisao Proposta

Adotar logs estruturados em JSON para o ambiente de producao:

1. Criar um formatter customizado `App\Logging\JsonLineFormatter` que estende `Monolog\Formatter\JsonFormatter`.
2. O formatter enriquece cada registro com:
   - `environment` (valor de `app.env`).
   - `app_name` (valor de `app.name`).
   - `request_id` (do header `X-Request-ID` ou gerado automaticamente pelo Laravel).
3. Adicionar o canal `json` e o stack `production` em `config/logging.php`:
   - `json` grava uma linha JSON por log em `storage/logs/laravel.log`.
   - `production` empilha os canais configurados via `LOG_STACK` (padrao: `daily`).
4. Em producao, `LOG_CHANNEL=production` e `LOG_STACK=daily,json` garantem que os logs sejam escritos tanto no formato legivel (daily) quanto no formato estruturado (json).

## Consequencias

- **Beneficios:**
  - Facilidade de ingestao por ferramentas de observabilidade.
  - Campos padronizados permitem correlacao por request ID e ambiente.
  - Mantem logs legiveis para desenvolvedores no canal `daily`.
- **Riscos:**
  - Formato JSON ocupa mais bytes por linha que texto simples, mas o ganho em parseabilidade compensa.
  - Campos sensiveis devem ser removidos do contexto antes de logar (responsabilidade dos desenvolvedores).
- **Estado atual:**
  - A implementacao ainda nao foi realizada. O `config/logging.php` mantem os canais padroes do Laravel (stack, single, daily, stderr etc.).
- **Alternativas rejeitadas:**
  - Usar apenas o canal `daily` padrao — rejeitado por dificultar a correlacao e analise em producao.
  - Integrar diretamente com um agente externo (ex.: Fluent Bit) — rejeitado por adicionar dependencia operacional no MVP.
