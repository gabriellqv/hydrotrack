# ADR 0001: Uso de Eventos para Invalidação de Cache

## Status

Proposto

## Contexto

O HydroTrack mantem caches agressivos no `DashboardService` para os endpoints de resumo (`dashboard:summary`), consumo (`dashboard:consumption:{dias}`) e mapa (`dashboard:map`). Esses caches reduzem drasticamente a carga do MySQL, mas precisam ser invalidados toda vez que os dados subjacentes mudam.

As mudancas podem vir de multiplos pontos:

- Ingestao de novas leituras (`IngestController` + `ReadingService`).
- Criacao, atualizacao ou exclusao de hidrometros (`HydrometerService`).
- Alertas resolvidos ou novos alertas gerados pelo `WatchdogCommand`.

Chamar `Cache::forget()` diretamente em cada ponto de mutacao tornaria a invalidacao difusa e propenso a erros de omissao.

## Decisao Proposta

Adotar o padrao **event-driven cache invalidation** do Laravel:

1. Criar um evento de dominio `HydrometerCacheInvalidated` (`app/Events/HydrometerCacheInvalidated.php`) disparado toda vez que um hidrometro e criado, atualizado ou excluido.
2. Criar o listener `InvalidateHydrometerCache` (`app/Listeners/InvalidateHydrometerCache.php`) que centraliza a chamada a `DashboardService::invalidateCache()`.
3. Manter invalidacoes diretas em `ReadingService` e `WatchdogCommand` para os casos em que a mudanca nao envolve hidrometro, mas afeta diretamente as metricas do dashboard.

O evento/listener sera registrado automaticamente pelo auto-discovery de eventos do Laravel.

## Consequencias

- **Beneficios:**
  - Invalidacao de cache centralizada e testavel.
  - Desacoplamento entre servicos de negocio e a camada de cache.
  - Facilidade para futuramente mover a invalidacao para uma fila ou broadcast.
- **Riscos:**
  - Novos pontos de mutacao de dados devem lembrar de disparar o evento ou invalidar o cache.
  - Em ambientes com multiplas instancias, o driver de cache precisa ser compartilhado (Redis recomendado).
- **Estado atual:**
  - A implementacao ainda nao foi realizada. A invalidacao de cache continua sendo feita manualmente via `DashboardService::invalidateCache()` nos Services.
- **Alternativas rejeitadas:**
  - Invalidar cache manualmente em cada controller — rejeitado por acoplamento e risco de esquecimento.
  - Observer de Eloquent no modelo `Hydrometer` — rejeitado para evitar disparos acidentais em atualizacoes internas massivas ou seeders.
