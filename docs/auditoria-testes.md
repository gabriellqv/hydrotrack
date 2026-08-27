# Auditoria de Testes — HydroTrack

**Data:** 21/08/2026
**Escopo:** backend (Laravel/Pest), frontend (Vue 3/Vitest), infraestrutura de testes e CI/CD.

---

## A. Resumo Executivo

**Estado atual:** O projeto possui uma suíte de testes **acima da média** para o porte, com boa disciplina de organização (pastas por domínio, factories, CI rodando em PR). São ~20 arquivos de teste no backend (Pest) e 7 no frontend (Vitest). A arquitetura "Controller magro + Service" foi bem aplicada e **facilita muito** a testabilidade.

**Principais problemas:**

1. **A regra de negócio mais crítica não tem teste unitário direto** — `ReadingService::ingest` (limiar de alto consumo, transição de status, criação de alerta) só é exercitada indiretamente, e o caminho `value_m3 > 10` **nunca é testado**.
2. **`WatchdogCommand` (detecção de offline) não tem nenhum teste** — é o coração do sistema de telemetria.
3. **`zero_reading` é comportamento fantasma** — o tipo de alerta existe no enum/migration/frontend, mas **nenhum código o gera**. Divergência entre documentação e implementação.
4. **Testes "Unit" que na verdade são de integração** — `HydrometerServiceTest` e `AlertServiceTest` usam `RefreshDatabase` e banco real (SQLite), mas estão classificados como unitários.
5. **Um teste de componente é frágil/trivial** — `CreateHydrometerModal.test.ts` faz assertion sem valor real.
6. **Lógica de negócio vazou para controllers** — `HydrometerController::show` (agregação de `chart_data`) e `readingsExport` (formatação CSV) violam o princípio "controller magro" e ficam sem teste.

**Principais riscos (não cobertos):**

- Ingestão IoT com consumo acima do limiar (P0).
- Detecção de dispositivos offline (P0).
- Invalidação de cache do dashboard (P1).
- Interceptor de 401 do Axios / redirecionamento de login (P1).
- Guard de rotas do Vue Router (P1).

**Nível de maturidade:** Intermediário. Há estrutura e disciplina, mas os testes concentram-se em CRUD/validação e deixam de fora justamente a lógica de domínio que diferencia o produto.

**Nota: 6/10.**

---

## B. Mapa de Testes

| Teste | Módulo | Tipo | Prioridade | O que valida | Motivo |
| ----- | ------ | ---- | ---------- | ------------ | ------ |
| Ingestão com consumo > 10 m³ gera alerta | ReadingService | Unit | P0 | Limiar de alto consumo, status→alert, criação de alerta | Regra de negócio central; hoje não testada |
| Ingestão com consumo ≤ 10 m³ NÃO gera alerta | ReadingService | Unit | P0 | Fronteira do limiar (edge case) | Evita falso positivo de alerta |
| Ingestão com código inexistente lança exceção | ReadingService | Unit | P1 | `firstOrFail` → ModelNotFound | Caminho de erro |
| Watchdog marca hidrômetros silenciosos como offline | WatchdogCommand | Integration | P0 | Detecção de offline + alerta em lote | Coração da telemetria; sem teste |
| Watchdog ignora hidrômetros com leitura recente | WatchdogCommand | Integration | P1 | Não gerar falso offline | Evita falso positivo |
| Watchdog respeita `--threshold` customizado | WatchdogCommand | Integration | P2 | Parâmetro de horas | Edge case de configuração |
| `generateRealisticValue` respeita faixas por tipo | SimulateIotCommand | Unit | P2 | Função pura, ranges por tipo | Fácil e barato de testar |
| `invalidateCache` limpa todas as chaves | DashboardService | Unit | P1 | Cache summary/consumption/map | Acoplamento oculto entre services |
| `getConsumptionChart` agrega por dia | DashboardService | Integration | P1 | Agregação SQL + cache | Métrica de dashboard |
| `getMapData` retorna campos corretos | DashboardService | Integration | P2 | Seleção de colunas | Formato do mapa |
| `resolve` de alerta já resolvido → 409 | AlertService/Controller | Integration | P1 | Idempotência | Já existe; manter |
| Logout revoga token | AuthController | Integration | P1 | `currentAccessToken()->delete()` | Segurança de sessão |
| `me` retorna usuário autenticado | AuthController | Integration | P2 | Endpoint de sessão | Fluxo de auth |
| Export CSV com BOM e separador `;` | HydrometerController | Integration | P2 | Formato do arquivo | Integração com Excel |
| `show` retorna `chart_data` e alertas limitados | HydrometerController | Integration | P2 | Agregação + eager load | Detalhe do hidrômetro |
| Middleware API key usa `hash_equals` | EnsureValidApiKey | Unit | P1 | Timing attack | Segurança |
| Interceptor 401 redireciona e limpa token | api.ts | Unit | P1 | Fluxo de sessão expirada | UX + segurança |
| Guard de rota redireciona não autenticado | router/index.ts | Unit | P1 | Proteção de rotas | Segurança |
| LoginView valida e-mail/senha client-side | LoginView | Unit | P2 | Validação + redirect | Fluxo de login |
| `timeAgo` formata corretamente | RecentAlerts | Unit | P3 | Função pura | Barato |
| `useIsAdmin` reflete role | useIsAdmin | Unit | P2 | RBAC no frontend | Autorização de UI |
| Toast store adiciona/remove com timeout | toast.ts | Unit | P3 | Fila de notificações | Comportamento isolado |

---

## C. Testes Existentes

### Backend (Pest) — avaliação geral: **bons**

| Arquivo | O que testa | Correto? | Problemas | Melhorias |
| --- | --- | --- | --- | --- |
| `IngestReadingTest` | Ingestão M2M (auth, validação, persistência) | Sim | **Não testa o caminho `value_m3 > 10`** (alerta de alto consumo) | Adicionar caso de consumo alto e de fronteira (exatamente 10.0) |
| `AlertServiceTest` | Listagem/filtro/resolução | Sim | Classificado "Unit" mas usa `RefreshDatabase` (é integração) | Mover para `Feature` ou renomear; testar `invalidateCache` chamado no `resolve` |
| `HydrometerServiceTest` | CRUD + filtros + cascade | Sim | Idem: "Unit" com banco real | Reclassificar |
| `HydrometerTest` (Model) | Scopes, relações, default | Parcial | "permite criação com atribuição explícita" testa o Eloquent, não regra de negócio (trivial) | Remover o teste trivial |
| `LoginTest` | Login | Sim | "retorna estrutura consistente" é **redundante** com "autentica com credenciais válidas" | Consolidar em um teste |
| `RegisterTest` | Registro | Sim | — | — |
| `DashboardSummaryTest` | Resumo do dashboard | Sim | Só testa `summary`, não `consumption`/`map` | Expandir |
| `AlertTest` (Feature) | Endpoints de alerta | Sim | — | — |
| `ErrorHandlingTest` | 404/401/403/422/headers | Sim | — | — |
| `Create/Update/Delete/ListHydrometerTest` | CRUD + RBAC | Sim | — | — |

### Frontend (Vitest) — avaliação geral: **razoável**

| Arquivo | O que testa | Correto? | Problemas | Melhorias |
| --- | --- | --- | --- | --- |
| `auth.test.ts` | Token, logout, fetchUser | Sim | Não testa `fetchUser` com erro (chama logout) | Adicionar caso de erro |
| `hydrometer.test.ts` | Listagem, paginação, loading | Parcial | "loading true durante fetch" usa `setTimeout` (frágil); não testa create/update/delete/export | Testar ações de mutação e erros |
| `dashboard.test.ts` | Resumo, consumo, mapa, alertas | Sim | Não testa caminho de erro (`handleError`) | Adicionar erro |
| `alert.test.ts` | Listagem, filtros, resolução | Sim | — | — |
| `BaseInput.test.ts` | Renderização, v-model | Sim | — | — |
| `StatusBadge.test.ts` | Classes por status | Sim | — | — |
| `CreateHydrometerModal.test.ts` | Renderização, submit | **Não** | O teste "emite close ao criar" faz assertion trivial (`form` ainda existe) e **não verifica** o `emit('close')` nem a chamada à API. Pode gerar falso positivo | Reescrever: mockar `createHydrometer`, verificar `emitted('close')` e chamada da store |

---

## D. Testes Ausentes (priorizados)

### P0 — Crítico

1. **`ReadingService::ingest` com `value_m3 > 10`** → deve criar alerta `high_consumption`, mudar status para `alert`, e persistir leitura. *(regra de negócio central, hoje sem teste)*
2. **`ReadingService::ingest` com `value_m3 == 10.0`** → **não** deve gerar alerta (fronteira do `>`). *(edge case do limiar)*
3. **`WatchdogCommand`** → hidrômetros com `last_reading_at` antigo (ou nulo) são marcados `offline` e geram alerta `offline` em lote. *(detecção de falha, sem teste)*

### P1 — Alto

4. **`ReadingService::ingest` com código inexistente** → lança `ModelNotFoundException`.
5. **`DashboardService::invalidateCache`** → limpa `summary`, `consumption:{7,30,90}` e `map`.
6. **`DashboardService::getConsumptionChart`** → agregação por dia respeita o período.
7. **`WatchdogCommand`** → hidrômetros com leitura recente **não** são afetados.
8. **`EnsureValidApiKey`** → rejeita key ausente/inválida; usa comparação segura.
9. **`AuthController::logout`** → revoga o token atual.
10. **Interceptor 401 do `api.ts`** → chama `logout()` e redireciona para login (exceto `/auth/logout`).
11. **Guard do `router/index.ts`** → redireciona não autenticado para `/login` preservando `redirect`; redireciona autenticado de `/login` para `/`.

### P2 — Médio

12. **`HydrometerController::show`** → retorna `chart_data` agregado e alertas limitados a 10.
13. **`HydrometerController::readingsExport`** → CSV com BOM UTF-8 e separador `;`.
14. **`SimulateIotCommand::generateRealisticValue`** → faixas por tipo (residential/commercial/industrial) e fallback.
15. **`useIsAdmin`** → reflete `role === 'admin'`.
16. **`LoginView`** → validação client-side (e-mail vazio/inválido, senha vazia) e redirect seguro (bloqueia `//`).
17. **`AuthController::me`** → retorna usuário autenticado.

### P3 — Baixo

18. **`RecentAlerts::timeAgo`** → formatação relativa (agora/min/h/d).
19. **`toast.ts`** → adiciona/remove com timeout e duração customizada.
20. **`useTheme`** → toggle persiste e aplica classe `light`.

---

## E. Testes que NÃO valem a pena

| Comportamento | Motivo |
| --- | --- |
| `Hydrometer::create` com status explícito (teste atual) | Testa o Eloquent/framework, não regra de negócio. Trivial. |
| Testes de renderização de `BaseButton`, `BaseBadge`, `BaseCard`, `Skeleton` | Componentes puramente visuais sem lógica; baixo risco, alto custo de manutenção. |
| `MapView.vue` (renderização Leaflet) | Acoplamento pesado a DOM/Leaflet/ResizeObserver/timers; testar exigiria mocks excessivos. Testar apenas as funções puras extraídas (`getMarkerColor`, `buildPopupContent`). |
| `ConsumptionChart` / `StatusDonutChart` (renderização Chart.js) | Canvas não é testável de forma confiável em jsdom; lógica é delegada à lib. |
| `SimulateIotCommand::handle` (loop infinito + `sleep`) | Não determinístico; testar apenas `generateRealisticValue`. |
| `HydrometerSeeder` / `DatabaseSeeder` | Dados de demonstração; risco baixo. |
| `AppServiceProvider` (vazio) | Sem lógica. |
| `zero_reading` (geração) | **Não existe implementação** — ver seção F. |

---

## F. Refatorações Recomendadas

1. **Extrair `chart_data` e CSV do `HydrometerController` para um Service** (ex.: `HydrometerService::getChartData()` e `ReadingExportService`). *Impacto:* remove lógica de negócio do controller, torna testável sem HTTP, e alinha com o princípio declarado no README. **Recomendado antes de escrever os testes P2 #12 e #13.**

2. **Tornar `ReadingService::createHighConsumptionAlert` testável** — hoje é `private` e acoplada a `Alert::create` + `Hydrometer::update`. Não precisa refatorar: o teste unitário via `RefreshDatabase` já cobre. **Não refatorar** (evitar over-engineering).

3. **Resolver o `zero_reading` fantasma** — o tipo existe no enum, na migration, no frontend e no README, mas **nenhum código o gera**. Decisão necessária: (a) implementar a detecção de leitura zerada, ou (b) remover o tipo. **Marcar como "necessita definição de requisito"** — não inventar comportamento.

4. **Reclassificar testes "Unit" que usam banco** — mover `AlertServiceTest`/`HydrometerServiceTest` para `tests/Feature` (ou renomear para refletir que são testes de integração). *Impacto:* clareza e honestidade sobre o que cada teste valida.

5. **Limpar boilerplate do `Pest.php`** — remover `something()` e a expectation `toBeOne` não utilizados.

6. **Padronizar `AuthController` para usar FormRequest** (como o resto do projeto) — hoje usa `$request->validate()` inline. *Impacto:* consistência e testabilidade da validação. **Opcional** (baixa prioridade).

---

## G. Estratégia Ideal

Baseada em risco, não em proporção fixa:

- **Unit (funções puras e regras de domínio):** `ReadingService` (limiar), `generateRealisticValue`, `timeAgo`, `useIsAdmin`, `useTheme`, `toast`, `invalidateCache`. ~30% do esforço.
- **Integration (banco + HTTP + middleware):** `WatchdogCommand`, `DashboardService` (agregações), `AuthController` (logout/me), `HydrometerController` (show/export), `EnsureValidApiKey`. ~40% do esforço — é onde está o maior risco não coberto.
- **E2E:** **Não recomendo** neste momento. O projeto é uma SPA + API com fluxos simples e já bem cobertos por integração. Um E2E (Playwright/Cypress) só se justificaria para o fluxo completo login→dashboard→mapa, mas o custo de infraestrutura supera o benefício atual. *Se* for adotado, limitar a 1 smoke test (login + dashboard carrega).
- **Contract/API tests:** O projeto já usa Scribe (OpenAPI). Um teste de contrato leve (validar que os Resources retornam o shape esperado) é coberto pelos testes de Feature existentes. **Não adicionar**.
- **Performance/Carga:** O endpoint `/api/ingest` (throttle 60/min) e o `WatchdogCommand` (bulk update/insert) são os candidatos, mas **não há requisito de escala** definido. **Não recomendar** sem definição de requisito.
- **Security:** Já coberto por `ErrorHandlingTest` (headers) e RBAC. Adicionar apenas o teste do `EnsureValidApiKey` (timing attack).

**Resumo:** foco em **Integration + Unit de domínio**, sem E2E, sem performance, sem contract.

---

## H. Roadmap de Implementação

### Fase 1 — P0 (crítico)

1. Testes do `ReadingService::ingest` (consumo alto, fronteira 10.0, código inexistente).
2. Testes do `WatchdogCommand` (offline, leitura recente, threshold).

### Fase 2 — P1 (regras de negócio e integrações importantes)

3. `DashboardService::invalidateCache` + `getConsumptionChart`.
4. `EnsureValidApiKey` (timing attack).
5. `AuthController::logout` e `me`.
6. Interceptor 401 do `api.ts` + guard do `router/index.ts`.

### Fase 3 — P2 (cobertura complementar)

7. Refatorar `HydrometerController` (extrair chart_data/CSV) e testar `show` + `readingsExport`.
8. `generateRealisticValue`, `useIsAdmin`, `LoginView`.

### Fase 4 — P3 (menor prioridade)

9. `timeAgo`, `toast`, `useTheme`.
10. Limpeza: reclassificar testes "Unit", remover boilerplate, corrigir `CreateHydrometerModal.test.ts`.

---

## I. Critérios de Qualidade

A suíte será considerada boa quando:

1. **Confiabilidade:** zero testes flaky (eliminar `setTimeout`/`sleep` em asserts; usar `vi.useFakeTimers`).
2. **Velocidade:** suíte backend < 30s, frontend < 15s (SQLite in-memory + mocks de Axios já garantem isso).
3. **Manutenibilidade:** cada teste testa **um comportamento** com nome descritivo (`it('gera alerta quando consumo excede 10 m3')`).
4. **Isolamento:** nenhum teste depende de ordem de execução ou estado compartilhado (já garantido por `RefreshDatabase` + `setActivePinia`).
5. **Cobertura de comportamento, não de linhas:** os caminhos P0/P1 (limiar de consumo, detecção de offline, RBAC, auth) devem estar 100% cobertos; componentes visuais podem ficar sem teste.
6. **Diagnóstico:** falha aponta para o comportamento quebrado, não para detalhe de implementação (evitar asserts em classes CSS internas).
7. **CI/CD:** testes rodam em todo PR (já configurado no `ci.yml`); adicionar **coverage report** apenas como métrica informativa, **não** como gate de merge.

---

## Conclusão

O projeto tem uma base sólida, mas os testes atuais protegem o "esqueleto" (CRUD, validação, auth) e deixam desprotegido o "coração" do produto — a ingestão IoT e a detecção de anomalias. A prioridade imediata é cobrir `ReadingService` e `WatchdogCommand`, e resolver a divergência do `zero_reading` antes de qualquer expansão de cobertura.
