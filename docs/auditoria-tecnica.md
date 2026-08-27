# Relatório Executivo de Auditoria Técnica — HydroTrack

**Data da auditoria:** 21/08/2026  
**Repositório:** `E:\Projetos\DEV\Emprego\hydrotrack`  
**Stack:** Laravel 13 (PHP 8.5) + Vue.js 3 + Pinia + TypeScript + Tailwind 4 + MySQL 8 + Sanctum

---

## 1. Resumo do projeto

O HydroTrack é uma plataforma de monitoramento de telemetria hídrica IoT. A arquitetura é monolítica separada em:

- **Backend Laravel** expondo API REST protegida por Sanctum e API key (M2M para ingestão).
- **Frontend Vue 3 SPA** consumindo a API via Axios + Pinia.
- **Simulador IoT** via comando Artisan (`hydrotrack:simulate`) e watchdog agendado (`hydrotrack:watchdog`) para detectar hidrômetros offline.
- **Dashboard analítico** com cards, gráficos Chart.js, mapa Leaflet e central de alertas.
- **RBAC simples** (`admin`/`operator`) via middleware.

O projeto entrega funcionalidade completa, possui testes (53 backend, 26 frontend), pipeline CI/CD com GitHub Actions, Docker Compose e lint/format configurados. A base arquitetural é adequada para o tamanho e objetivo, mas há problemas reais de segurança, consistência, DX e alguns bugs/limitações de lógica que impedem que ele seja considerado código Pleno forte.

---

## 2. Arquitetura encontrada

- **Backend:** Controller magro + Service + Eloquent. Form requests para validação, API Resources para saída.
- **Frontend:** Views → Pinia Stores → `services/api.ts` → backend. Componentes reutilizáveis em `components/ui/`.
- **Autenticação:** Sanctum token stateless para SPA + API key (`INGEST_API_KEY`) para endpoint `/api/ingest`.
- **Banco:** MySQL/SQLite, migrations com índices razoáveis, foreign keys com `cascadeOnDelete`.
- **Cache:** `Cache::remember` no dashboard com invalidação via `DashboardService::invalidateCache()`.
- **Scheduler:** Agendamento no `routes/console.php` (`hydrotrack:watchdog` a cada 5 min).
- **Infra:** Docker multi-stage backend (Alpine PHP 8.4) + frontend Node dev server. Dockerfile backend tem entrypoint que gera `.env` com variáveis do Render.

A arquitetura **não é ruim** para o escopo. Não recomendo introduzir DDD/Clean Architecture. Os gargalos estão em segurança, consistência de contratos, algumas más práticas no frontend e DX, não na arquitetura em si.

---

## 3. Principais problemas

| Prioridade | Problema |
|---|---|
| **P0 (CRÍTICO)** | Segredos e configuração sensível expostos/arquitetura de segurança fraca (senha padrão em seeder, token revogação total no login, API key vazia por padrão sem aviso, Dockerfile gera `.env` com envvars potencialmente sensíveis, `.env` real versionado/incorreto) |
| **P0 (CRÍTICO)** | `AlertController::resolve` expõe `RuntimeException` genérico sem log; alertas podem ser resolvidos por qualquer usuário autenticado (incluindo operadores), apesar da UI esconder o botão |
| **P0 (CRÍTICO)** | `MapView.vue` usa `innerHTML` com interpolação de dados do backend → risco de XSS |
| **P0 (CRÍTICO)** | CORS `supports_credentials: false` contradiz uso de Sanctum token Bearer; se a aplicação for SPA com cookie, está quebrado. Como usa Bearer, está OK, mas a configuração é confusa |
| **P1 (ALTO)** | Inconsistência de contratos de paginação e naming (`per_page` backend vs frontend não valida, `PaginatedResponse` usa `meta` mas Laravel inclui `links`; dashboard consome `/alerts` com `data` mas lida com fallback frágil) |
| **P1 (ALTO)** | `HydrometerController::show` coloca lógica de query/charts no controller (violação da promessa de controllers magros) e `chart_data` é atribuído dinamicamente sem cast |
| **P1 (ALTO)** | `ReadingService::ingest` não usa transação; `last_reading_at` + status + alerta + cache invalidação podem deixar banco inconsistente em falha |
| **P1 (ALTO)** | `WatchdogCommand` usa `where('status', '!=', 'offline')->where(last_reading_at < cutoff)` — dispositivos que nunca enviaram leitura (`last_reading_at = null`) são considerados offline imediatamente, o que pode ser correto ou não, mas gera alertas massivos no primeiro run |
| **P1 (ALTO)** | Não há rate limiting no frontend para ingestão M2M real nem retry/backoff; polling de 5s/15s no dashboard sem cache local de ETag |
| **P1 (ALTO)** | Frontend usa `await store.fetch...()` no top-level de views com `<Suspense>`, mas não há fallback/loading consistente nem tratamento de erro em várias stores (`dashboard`, `alert`) |
| **P1 (ALTO)** | `useTheme` é singleton global (ref compartilhado entre instâncias Vue) — funciona, mas é antipattern |
| **P1 (ALTO)** | CI configura PHP 8.4 no workflow, mas `composer.json` e Dockerfile declaram PHP 8.3/8.5; README diz PHP 8.5 |
| **P2 (MÉDIO)** | Formulários de create/edit convertem lat/lng manualmente e usam `as unknown as ...` para burlar TypeScript |
| **P2 (MÉDIO)** | `DashboardView.vue` renderiza ribbon com 6 cards mas sem loading states individuais; `summary` pode ser `null` e mostra `—` |
| **P2 (MÉDIO)** | `StatusDonutChart` tooltip usa `dataset.data.reduce` a cada hover — OK para poucos dados, mas computação desnecessária |
| **P2 (MÉDIO)** | `HydrometerSeeder` gera endereço com `Rua {bairro}, {num}` e alertas com mensagens fixas que não refletem regra real |
| **P2 (MÉDIO)** | `HydrometerResource` inclui `chart_data` como dynamic property sem tipagem; expõe `readings` e `alerts` only when loaded |
| **P2 (MÉDIO)** | Backend: `EnsureIsAdmin` assume que `request->user()` nunca é null; retorna JSON hardcoded |
| **P2 (MÉDIO)** | Ausência de logging estruturado para ingestão, alertas e autenticação |
| **P2 (MÉDIO)** | Frontend: `LoginView.vue` valida email duas vezes (HTML5 + JS); não usa `BaseInput` type="email" nativamente |
| **P2 (MÉDIO)** | Testes do frontend são poucos e frágeis (`CreateHydrometerModal.test.ts` não testa submissão real) |
| **P3 (BAIXO)** | Pontos de melhoria de organização: `ToastContainer.vue` fora da pasta `ui`; `POLLING_INTERVAL` duplicado entre views; CSS `view-scroll-layout` altura baseada em `--layout-padding` definido como `2rem` mas sem fallback |
| **P3 (BAIXO)** | Backend: `AuthController::login` revoga **todos** os tokens do usuário a cada login — conveniente mas força logout em dispositivos múltiplos |

---

## 4. Bugs encontrados

### 4.1 Alerta pode ser resolvido por qualquer usuário autenticado (operador ou admin)

**Local:** `backend/routes/api.php:50` + `backend/app/Http/Controllers/AlertController.php:43`

**Problema:** A rota `PATCH /alerts/{alert}/resolve` está dentro do grupo `auth:sanctum` mas **não** passa pelo middleware `admin`. O backend não faz nenhuma autorização. A UI esconde o botão para não-admins (`AlertItem.vue`), mas a API é vulnerável a IDOR/mass assignment.

**Impacto:** Operador pode resolver alertas críticos, violando RBAC.

**Correção:** Adicionar `->middleware('admin')` na rota ou delegar autorização a uma policy.

```php
Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->middleware('admin');
```

**Prioridade:** P0 — CRÍTICO

---

### 4.2 `WatchdogCommand` marca como offline hidrômetros com `last_reading_at = null`

**Local:** `backend/app/Console/Commands/WatchdogCommand.php:54-59`

**Problema:** A query `whereNull('last_reading_at')` junto com `status != 'offline'` faz com que hidrômetros recém-criados (sem leitura) sejam marcados offline e gerem alertas imediatamente.

**Impacto:** Seeder cria 200 hidrômetros com `last_reading_at` preenchido, mas em produção um novo hidrômetro criado fica offline instantaneamente.

**Correção:** Decidir se novo hidrômetro deve começar online ou offline. Se quiser tolerar sem leitura, ignore `last_reading_at` nulo ou defina `last_reading_at` no momento da criação.

```php
// Opção 1: ignorar nulos
->where('last_reading_at', '<', $cutoff)

// Opção 2: definir last_reading_at ao criar
'HydrometerService::create' => ['status' => 'online', 'last_reading_at' => now()]
```

**Prioridade:** P1 — ALTO

---

### 4.3 `HydrometerController::show` não valida `days` e faz query no controller

**Local:** `backend/app/Http/Controllers/HydrometerController.php:53-68`

**Problema:** `$days = (int) $request->query('days', 30);` aceita valores negativos ou enormes. A query de `chart_data` e o `load` de `alerts` estão no controller em vez de no service. Além disso, `chart_data` é setado como propriedade dinâmica no model.

**Impacto:** Inconsistência arquitetural, possível DoS com `days=999999`.

**Correção:** Mover lógica para `HydrometerService` e validar days.

```php
$days = min(max((int) $request->query('days', 30), 1), 365);
$hydrometer = $this->service->getDetails($hydrometer, $days);
```

**Prioridade:** P1 — ALTO

---

### 4.4 `ReadingService::ingest` falha silenciosa em partes

**Local:** `backend/app/Services/ReadingService.php:38-59`

**Problema:** Não há transação. Se `Alert::create` falhar, a leitura foi criada mas o status do hidrômetro pode ficar inconsistente.

**Impacto:** Dados inconsistentes entre `readings`, `hydrometers` e `alerts`.

**Correção:** Usar `DB::transaction` ao redor das operações.

```php
return DB::transaction(function () use ($payload) {
    $hydrometer = Hydrometer::where('code', ...)->firstOrFail();
    $reading = Reading::create(...);
    $hydrometer->update(...);
    if (...) $this->createHighConsumptionAlert(...);
    DashboardService::invalidateCache();
    return $reading;
});
```

**Prioridade:** P1 — ALTO

---

### 4.5 Dashboard frontend faz polling excessivo sem backoff ou cache

**Local:** `frontend/src/views/DashboardView.vue:39-65` e `frontend/src/views/MapPageView.vue:30`

**Problema:** Polling a cada 15s (dashboard) e 5s (mapa) sem verificar se a aba está ativa, sem ETag, sem AbortController. Causa requisições desnecessárias.

**Impacto:** Carga no backend e possível memory leak se componente for remontado.

**Correção:** Usar `document.visibilityState`, AbortController e/ou Server-Sent Events/WebSockets para notificações.

```ts
let controller: AbortController | null = null
async function refreshDashboard() {
  controller?.abort()
  controller = new AbortController()
  await Promise.all([...].map(fn => fn(controller.signal)))
}
```

**Prioridade:** P1 — ALTO

---

### 4.6 `MapView.vue` usa `innerHTML` com dados dinâmicos

**Local:** `frontend/src/components/MapView.vue:94-102`

**Problema:** `popupContent.innerHTML = \`...\${h.address}...\`` permite XSS se o backend retornar HTML/JS em `address`, `code` ou `neighborhood`.

**Impacto:** XSS armazenado/refletido.

**Correção:** Usar text nodes do DOM ou sanitizar com `DOMPurify`/`textContent`.

```ts
const codeLink = document.createElement('a')
codeLink.textContent = h.code
codeLink.href = '#'
codeLink.addEventListener('click', e => { e.preventDefault(); router.push(...) })
popupContent.appendChild(codeLink)
```

**Prioridade:** P0 — CRÍTICO

---

## 5. Problemas de segurança

| # | Severidade | Problema | Local |
|---|---|---|---|
| 1 | CRÍTICO | Senha padrão `admin123` hardcoded no seeder | `database/seeders/DatabaseSeeder.php:31` |
| 2 | CRÍTICO | `INGEST_API_KEY` vazio por padrão; deploy pode subir sem API key | `.env.example:32`, `config/services.php:39` |
| 3 | CRÍTICO | Qualquer usuário autenticado resolve alertas (falta autorização) | `routes/api.php:50` |
| 4 | CRÍTICO | XSS em popup do mapa via `innerHTML` | `frontend/src/components/MapView.vue:95` |
| 5 | ALTO | Login revoga todos os tokens → impede múltiplos dispositivos, potencial DoS em usuário | `AuthController.php:73` |
| 6 | ALTO | `EnsureIsAdmin` não verifica se usuário existe; retorna JSON local | `EnsureIsAdmin.php:22` |
| 7 | ALTO | CORS `supports_credentials: false` e `allowed_origins` único sem validação | `config/cors.php` |
| 8 | ALTO | Dockerfile backend grava secrets em `.env` no container (não usa secrets manager nativo) | `backend/Dockerfile:61` |
| 9 | MÉDIO | `AuthController::register` não define `role`, permitindo `role` no request? Não, pois `User` usa `#[Fillable]` mas request não valida `role` | `AuthController.php:35`, `User.php:22` |
| 10 | MÉDIO | Rate limit `throttle:120,1` generoso demais para APIs de dashboard | `routes/api.php:28` |
| 11 | MÉDIO | `.env` real do backend parece versionado (encontrado `backend/.env`) | `backend/.env` |
| 12 | BAIXO | README expõe credenciais de teste (`admin@hydrotrack.com / admin123`) | `README.md:217-218` |

**Observação:** O `.env` do backend está presente no disco. Se estiver no `.gitignore` correto, não há exposição no repositório, mas é um risco local. Verifique se não foi commitado.

---

## 6. Problemas de arquitetura

1. **Inconsistência Controller/Service:** `HydrometerController::show` faz query de chart data; os outros controllers são magros. Isso quebra a promessa da arquitetura.
2. **Acoplamento frontend/backend via strings:** URLs e nomes de campos replicados em stores e views (`neighborhood`, `status`, `type`, `per_page`). Não há contrato compartilhado (OpenAPI client gerado).
3. **Cache invalidation centralizada:** `DashboardService::invalidateCache()` é chamada de IngestController e AlertService. Funciona, mas escala mal se houver muitas chaves.
4. **Polling em vez de push:** Dashboard e mapa usam polling. Para um MVP está OK, mas em produção deveria usar SSE ou filas.
5. **RBAC baseado apenas em string `role`:** Sem policies, sem gates, sem middleware de autorização em nível de recurso.

---

## 7. Complexidade desnecessária

1. **Composables `useIsAdmin` e `useTheme`:** `useIsAdmin` é uma computed trivial que poderia ser feita inline em poucos lugares. `useTheme` mantém estado global em um ref fora do composable — funciona, mas é acidental.
2. **DashboardSkeleton muito detalhado:** Skeleton personalizado que replica layout exato. Poderia ser mais genérico.
3. **`ConsumptionChart.vue` e `StatusDonutChart.vue`:** Ambos mantêm `displayedData` separada de `fullChartData` com `setTimeout` para animação. Isso adiciona estado e atraso artificial sem necessidade; Chart.js já anima.
4. **`CreateHydrometerModal.vue` / `EditHydrometerModal.vue`:** Lógica de conversão de tipos lat/lng duplicada. Poderia ser um composable/form schema compartilhado.
5. **Backend `Controller.php` abstrato vazio:** Classe base sem comportamento. Pode ser mantida para futuro, mas hoje é boilerplate.
6. **Múltiplos `@theme` duplicados no CSS:** `--color-surface` e outros definidos duas vezes.

---

## 8. Código que pode ser simplificado

- **Stores `dashboard.ts` e `alert.ts`:** não tratam erro (`try/catch`). Podem propagar erro ou usar composable de fetch genérico.
- **`HydrometerDetailView.vue`:** `readingsToChartData()` retorna array desnecessariamente; pode usar computed.
- **`MapPageView.vue`:** `filterCounts` recalcula 4 filtros; pode ser um computed com reduce.
- **`AlertItem.vue`:** usa `RouterLink` para `/hydrometers` em vez de `/hydrometers/:id` — link quebrado/sem utilidade real.
- **`App.vue`:** importa componentes que só são usados em fallback de Suspense. Poderia lazy-load.

---

## 9. Código duplicado

| Código | Localizações |
|---|---|
| Conversão lat/lng string → number | `CreateHydrometerModal.vue`, `EditHydrometerModal.vue` |
| Mapeamento status → cor/label | `frontend/constants`, `MapView.vue`, `StatusBadge.vue`, `StatusDonutChart.vue`, `RecentAlerts.vue` |
| Tooltip/legendas de período | `DashboardView.vue`, `HydrometerDetailView.vue` |
| Polling setup/teardown | `DashboardView.vue`, `MapPageView.vue` |
| Sanctum token no localStorage | `auth.ts` |
| Fetch com loading boolean | todas as stores |

---

## 10. Código morto

- **`frontend/src/components/__tests__/CreateHydrometerModal.test.ts`:** Teste não asserta submissão real (linha 48 apenas verifica que form existe).
- **`backend/app/Console/Commands/` exemplo comentado `@example php artisan hydrotrack:simulate --interval=5 --count=20` não é código morto, mas documentação redundante.
- **`frontend/src/components/ui/ToastContainer.vue`:** O arquivo `ToastContainer.vue` está em `components/`, não em `components/ui/`, apesar do import no `App.vue` apontar para `@/components/ToastContainer.vue`. Não há código morto, mas organização inconsistente.
- **`.env` real do backend:** se versionado, é configuração ativa/morta misturada.

---

## 11. Problemas de performance

1. **Polling sem `visibilityState`:** requisições continuam em aba inativa.
2. **Mapa recria todos os markers a cada watch de `props.hydrometers`:** com 200 hidrômetros, OK; com 10k, vazamento. `markersLayer.clearLayers()` + recriação é O(n).
3. **`DashboardService::getMapData()` sem limite:** `Hydrometer::select(...)->get()` carrega todos os registros. Hoje são 200, mas sem paginação.
4. **`HydrometerController::show` carrega até 365 dias de leituras agregadas por dia:** OK, mas sem limite de `days`.
5. **`StatusDonutChart` recalcula total a cada hover:** `dataset.data.reduce` no tooltip callback.

---

## 12. Problemas de testes

| Problema | Local |
|---|---|
| `Pest.php` tem `RefreshDatabase` comentado globalmente | `tests/Pest.php:18` |
| Testes de frontend `CreateHydrometerModal` não testam criação real | `CreateHydrometerModal.test.ts` |
| Não há teste para watchdog, ingest com alerta de alto consumo, resolução de alerta por operador negada | geral |
| Não há testes E2E | — |
| `DashboardSummaryTest` testa apenas summary, não consumption/map/alerts | `tests/Feature/Dashboard/DashboardSummaryTest.php` |
| Frontend: testes de stores mockam `api` mas não testam erro | geral |

---

## 13. Problemas de DX

1. **README contradiz versões:** diz PHP 8.5, mas workflow CI usa 8.4 e `composer.json` requer `^8.3`.
2. **`backend/Dockerfile` usa PHP 8.4 CLI Alpine, mas README/infra local fala PHP 8.5.**
3. **Scripts do `package.json` root falham no Windows:** `cd backend && vendor/bin/pint --test` usa `&&`, mas no PowerShell funciona; no entanto, assume que `vendor` existe e `php` está no PATH.
4. **CI não roda `npm audit` nem `composer audit`.**
5. **Sem `AGENTS.md`:** o sistema recomenda esse arquivo; não existe.
6. **Sem `.env.example` no root para Docker Compose.** O `docker-compose.yml` define DB via env, mas `.env.example` separado pode confundir.
7. **Pint falha em LF/CRLF:** `vendor/bin/pint --test` reportou `line_ending` em todos os arquivos porque o repo está em CRLF. Isso faz CI falhar em Linux.
8. **Frontend `npm run lint` aplica `--fix`:** no CI isso não detecta problemas não corrigidos.

---

## 14. Nota técnica

| Critério | Nota | Justificativa |
|---|---|---|
| Arquitetura | 6.5 | Base sensata (Controller/Service), mas quebra próprias regras em `show`, falta policies, cache invalidation centralizada, sem eventos. |
| Código | 6.0 | Funcional, limpo em partes, mas com inconsistências, lógica duplicada, innerHTML, awaits no top-level sem erro. |
| TypeScript | 7.0 | Tipagem razoável, sem `any` gritante, mas usa `as unknown as` para burlar e não cobre erros de API runtime. |
| Backend | 6.5 | Laravel bem usado, testes bons, mas falhas de segurança (autorização, transação, secrets), queries no controller. |
| Frontend | 6.0 | Vue 3/Pinia usados corretamente, UI consistente, mas XSS, polling, tratamento de erro fraco, testes frágeis. |
| Banco de dados | 7.0 | Índices e FKs presentes, schema coerente, mas sem transações e sem índice em `alerts.resolved` (só composto). |
| Segurança | 4.0 | Problemas críticos de autorização, XSS, secrets, CORS confuso. |
| Performance | 6.0 | Cache existe, mas polling agressivo e recriação de markers sem otimização. |
| Testes | 6.5 | Cobertura média, 53 backend/26 frontend, mas faltam cenários negativos de segurança e E2E. |
| Manutenibilidade | 6.0 | Estrutura clara, mas duplicação, contratos implícitos e documentação desatualizada dificultam. |
| Developer Experience | 5.5 | Docker/CI existe, mas versões inconsistentes, Pint falha por CRLF, lint `--fix` no CI, sem AGENTS.md. |
| Organização | 6.5 | Pastas bem nomeadas, mas `ToastContainer.vue` fora de `ui`, duplicação de constantes. |
| **Geral** | **6.0 / 10** | Projeto funcional e completo, mas com falhas de segurança e consistência que impedem considerá-lo Pleno forte. |

---

## 15. Nível atual

**Atual:** entre **Júnior forte** e **Pleno**.

**O que impede de ser Pleno:**

- Falhas de segurança reais (autorização, XSS, secrets).
- Contratos entre frontend/backend implícitos e frágeis.
- Lógica de negócio vazando para controller (`show`).
- Tratamento de erro e loading inconsistente no frontend.
- Testes não cobrem cenários críticos de segurança/erro.
- DX com inconsistências de versão e lint.

**O que falta para Pleno forte:**

- Adotar autorização via policies/gates.
- Transações em operações críticas.
- Sanitização/validação reforçada.
- Contratos de API compartilhados (DTO/TS generated).
- Tratamento de erro uniforme no frontend.
- Testes de segurança e E2E.
- Documentação de setup e decisões técnicas (`AGENTS.md`).

**Aspectos já acima do nível Júnior forte:**

- Uso de Services, Resources, Form Requests.
- Testes automatizados com Pest e Vitest.
- CI/CD com lint + testes.
- Cache e agendamento.
- Componentização e design system básico.

---

## 16. Top 10 melhorias mais importantes

1. **Corrigir autorização da resolução de alertas** (P0)
2. **Eliminar XSS no MapView** (P0)
3. **Remover secrets hardcoded e proteger configuração** (P0)
4. **Usar transações em `ReadingService::ingest`** (P1)
5. **Mover lógica de `HydrometerController::show` para Service** (P1)
6. **Adicionar abort/pause de polling e tratamento de erro nas stores** (P1)
7. **Corrigir inconsistências de versão PHP no CI/Docker/README** (P1)
8. **Adotar policies para RBAC e não confiar apenas na UI** (P1)
9. **Criar `AGENTS.md` e melhorar README de setup** (P2)
10. **Expandir testes para cobrir segurança e erros** (P2)

---

## 17. Plano de refatoração em ordem de prioridade

### P0 — Corrigir imediatamente

1. Adicionar `middleware('admin')` em `PATCH /alerts/{alert}/resolve`.
2. Refatorar `MapView.vue` para não usar `innerHTML`.
3. Remover/variabilizar senha padrão do seeder (exigir env `ADMIN_PASSWORD`).
4. Garantir que `.env` real do backend não esteja versionado; adicionar `.env` ao `.gitignore` se necessário.
5. Validar que `INGEST_API_KEY` não pode ser vazio em produção (`EnsureValidApiKey` rejeita vazio, mas deploy pode deixar sem config).

### P1 — Próxima etapa

6. Envolver `ReadingService::ingest` em `DB::transaction`.
7. Mover `chart_data` e `alerts` de `HydrometerController::show` para `HydrometerService` e validar `days`.
8. Criar `AlertPolicy`/`HydrometerPolicy` e usar `authorize`/`can` no backend.
9. Implementar `AbortController` + `document.visibilityState` no polling.
10. Padronizar versões PHP: CI 8.5, Dockerfile 8.5, `composer.json` `^8.4`.
11. Adicionar tratamento de erro nas stores `dashboard`, `alert`.
12. Adicionar testes para cenários de segurança (operador tentando resolver alerta, etc.).

### P2 — Melhorias

13. Criar `AGENTS.md` com setup, decisões e problemas conhecidos.
14. Refatorar `Create/EditHydrometerModal` para compartilhar schema de form.
15. Melhorar `StatusDonutChart` tooltip performance.
16. Centralizar mapeamentos status/cor/label em um único lugar.
17. Adicionar logs estruturados para ingestão e alertas.
18. Expandir testes frontend (erro de API, submit real).

### P3 — Nice to have

19. SSE/WebSockets para atualizações em tempo real (substituir polling).
20. Paginação no endpoint de mapa para grande volume.
21. OpenAPI client generation para frontend.
22. Storybook/documentação de componentes UI.

---

## 18. Conclusão

O HydroTrack é um projeto entregue e funcional, com boa base arquitetural e bastante esforço em qualidade (testes, CI, lint, documentação). No entanto, ele ainda carrega problemas típicos de projetos acelerados: **segurança não revisada**, **contratos implícitos**, **tratamento de erro superficial** e **alguma lógica vazendo de camada**. Não recomendo reescrever a arquitetura. O caminho para nível Pleno forte passa por corrigir os itens P0/P1, manter a simplicidade e expandir cobertura de testes com foco em segurança e casos de erro.
