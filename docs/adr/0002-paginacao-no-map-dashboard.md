# ADR 0002: Paginação no Mapa do Dashboard

## Status

Aceito

## Contexto

O endpoint `GET /api/dashboard/map` retorna todos os hidrômetros com coordenadas geográficas para renderização no mapa interativo (Leaflet). A base de dados pode crescer rapidamente, especialmente com seeders de demonstração contendo 200+ hidrômetros.

A questão central era: devemos paginar os hidrômetros no mapa ou retorná-los todos de uma vez?

## Decisão

Não paginar o mapa do dashboard. Retornar a lista completa de hidrômetros em uma única requisição e cachear o resultado por 5 minutos (`CACHE_TTL_MAP = 300s`) no `DashboardService`.

Essa escolha se baseia nos seguintes fatores:

1. **UX do mapa:** Paginar markers geográficos introduz complexidade na interface (clusters, carregamento sob demanda, bounds) que foge ao escopo do MVP.
2. **Tamanho do payload:** Cada hidrômetro no mapa retorna apenas campos selecionados (`id`, `code`, `latitude`, `longitude`, `address`, `neighborhood`, `type`, `status`, `last_reading_at`). Mesmo com milhares de registros, o payload JSON permanece pequeno.
3. **Cache:** O cache de 5 minutos reduz a carga no banco, já que a posição dos hidrômetros muda com pouca frequência.
4. **Listagens administrativas:** Outras listagens, como `/api/hydrometers` e `/api/alerts`, usam `LengthAwarePaginator` com filtros e paginação, atendendo às necessidades de CRUD e gestão de alertas.

## Consequências

- **Benefícios:**
  - Implementação simples do frontend (um único fetch popula o mapa).
  - Baixa latência percebida graças ao cache.
  - Sem dependência de bibliotecas de clustering ou virtualização no mapa.
- **Riscos:**
  - Crescimento muito grande da frota pode aumentar o tempo de transferência. Neste caso, a estratégia deve ser revista (ex.: carregar apenas hidrômetros visíveis no viewport ou usar GeoJSON simplificado).
- **Alternativas rejeitadas:**
  - Paginar o mapa com `LengthAwarePaginator` — rejeitado por dificultar a experiência de pan/zoom contínuo.
  - Retornar GeoJSON comprimido — rejeitado por adicionar complexidade desnecessária no MVP.
