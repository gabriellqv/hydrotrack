# Develop → Main: Análise de Prontidão

> Data: 2026-08-27
> Escopo: comparação entre `develop` e `main` do repositório HydroTrack
> Autor: análise automatizada (Tech Lead / Full Stack Pleno)

---

## 1. Resumo executivo

O HydroTrack é uma plataforma de telemetria hídrica IoT (Laravel 13 + Vue 3 + MySQL) bem estruturada, com separação clara entre Controller/Service/Resource no backend e Stores/Components/Views no frontend. A branch `develop` está **27 commits à frente** de `main`, com **88 arquivos alterados** (+3889 / -664 linhas), concentrados em endurecimento de segurança, correção de bugs críticos, ampliação da suíte de testes e reescrita da infraestrutura Docker/CI-CD.

Todas as validações automatizadas **passam**: 114 testes backend (323 assertions), 46 testes frontend, Pint, ESLint, Oxlint, Prettier, `vue-tsc` e build de produção. Não há bug crítico evidente nem build quebrado.

Entretanto, a branch **não está pronta para merge** por um motivo específico e objetivo: **há uma divergência entre a documentação de decisões (ADRs) e o código real**. Quatro ADRs estão marcadas como "Aceito" descrevendo funcionalidades que **não foram implementadas** (eventos de invalidação de cache, logs JSON, CSP restritivo com nonce e testes E2E com Playwright). Além disso, artefatos de uma execução E2E **falha** ficaram no diretório de trabalho, e o diretório `docs/` (referenciado pelo README) **não está versionado** no repositório.

O veredito é **NÃO APTO PARA MERGE**, mas o plano de correção é **curto e focado** — não exige reescrita de arquitetura, apenas alinhar documentação com implementação e fechar alguns itens de segurança/consistência.

---

## 2. Status do merge

**NÃO APTO PARA MERGE**

---

## 3. Visão geral do projeto

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13, PHP 8.4, Eloquent, MySQL 8, Sanctum |
| Frontend | Vue 3, TypeScript, Pinia, Vue Router, Tailwind 4, Vite |
| Mapas/Gráficos | Leaflet, vue-chartjs/Chart.js |
| Testes | Pest PHP (backend), Vitest + Vue Test Utils (frontend) |
| DevOps | Docker Compose, GitHub Actions (CI + Deploy) |
| Qualidade | Laravel Pint, ESLint + Oxlint + Prettier, Husky + lint-staged + Commitlint |

**Arquitetura:** Controller magro → Service (regra de negócio) → Form Request (validação) → API Resource (formatação). Frontend com Stores Pinia isoladas por domínio e cliente HTTP centralizado (Axios) com interceptadores.

**Fluxo crítico:** ingestão M2M (`/api/ingest`) → `ReadingService` (persistência + detecção de anomalias) → Watchdog (detecção de offline) → dashboard com cache → resolução de alertas por admin.

---

## 4. Comparação entre develop e main

### 4.1 Commits relevantes (main..develop)

| Commit | Resumo |
|---|---|
| `1c0bb95` | Correções críticas de segurança |
| `257fbec` | Tratamento global de erros + headers de segurança |
| `f9178c0` | Índices de banco + melhorias de cache |
| `c5e8c55` | Extração do serviço de alertas do controller |
| `50d4e5b` | Centraliza constantes, extrai modais, corrige vazamentos no mapa |
| `e464ada` | Testes de stores/componentes + documentação de arquitetura |
| `0b84fc4` | Melhora workflow CI + padroniza scripts de qualidade |
| `98332ec`/`10eb116` | Corrige XSS no mapa, polling inteligente, tratamento de erro |
| `292416c` | Segurança RBAC, transações, logs, padronização PHP 8.4 |
| `e792f91`..`66ab551` | Cobertura de testes (regras de negócio, segurança, cache, qualidade) |
| `d02d5be` | Detecção de leitura zerada na ingestão |
| `9e22e4e` | `.gitattributes` (LF) |
| `90530e8` | Dockerfile backend reescrito (php-fpm + nginx + supervisord) |
| `421a8c4` | CI melhorado + deploy automatizado (Render) |
| `cbfa281` | Remove credenciais hardcoded + corrige versões no README |
| `8d787e7` | Deduplicação de alertas, health check real, tratamento de rede |
| `94932a2` | Melhorias críticas de auditoria técnica |
| `238bf14` | Rate limit por IP, tipagem dos modais, testes de componentes |
| `caa8953` | Melhorias P1 de auditoria técnica |
| `d3cf507` | Auditoria de dependências no CI, limita toasts, otimiza watchdog |
| `55c0de1` | Melhorias P2 de auditoria técnica |

### 4.2 Funcionalidades adicionadas

- Deduplicação de alertas (`unresolvedAlertExists`).
- Health check real (`/health` com verificação de banco).
- Rate limit por IP no endpoint de autenticação.
- Tratamento de erro de rede no frontend (`ERR_NETWORK`).
- Tipagem correta dos modais (payloads `CreateHydrometerPayload`/`UpdateHydrometerPayload`).
- Limite de toasts simultâneos (`MAX_VISIBLE_TOASTS = 5`).
- Validação de `reading_at` (rejeita datas futuras).
- `token_prefix` do Sanctum (`hydrotrack_`).
- Pipeline CI (Pint + Pest + ESLint + type-check + build) e Deploy (Render).
- Dockerfile backend multi-stage (php-fpm + nginx + supervisord), migration separada do boot.
- Seeder exige `ADMIN_SEED_PASSWORD`/`OPERATOR_SEED_PASSWORD` (não imprime senhas).

### 4.3 Alterações de banco

- Índices adicionados em `hydrometers` (`neighborhood`, `status`, `type`) e `readings` (`hydrometer_id+reading_at`, `reading_at`).
- Nenhuma migration destrutiva ou irreversível. Sem breaking change de schema.

### 4.4 Alterações de API / contratos

- Novo endpoint público `GET /api/health`.
- `reading_at` agora rejeita datas futuras (mudança de validação — pode afetar clientes M2M que enviem timestamp futuro).
- Sem breaking change de contrato JSON (Resources mantêm o shape).

### 4.5 Alterações de configuração

- `CACHE_STORE`, `INGEST_API_KEY`, `ADMIN_SEED_PASSWORD`, `OPERATOR_SEED_PASSWORD`, `RUN_MIGRATIONS`, `SANCTUM_TOKEN_PREFIX` adicionados ao `.env.example`.
- `commit_msg.txt` (residual) removido.

### 4.6 Possíveis regressões

- **Baixo risco:** a validação mais rígida de `reading_at` pode rejeitar leituras com relógio do sensor adiantado. Mitigado por ser uma regra de integridade desejada.

---

## 5. Validações executadas

| Validação | Comando | Resultado |
|---|---|---|
| Testes backend (Pest) | `php artisan test` | ✅ 114 testes, 323 assertions, todos passando |
| Code style backend (Pint) | `vendor/bin/pint --test` | ✅ passou |
| Type check frontend | `npm run type-check` | ✅ passou |
| Lint frontend (Oxlint + ESLint) | `npm run lint` | ✅ 0 warnings, 0 errors |
| Formatação frontend (Prettier) | `npm run format:check` | ✅ passou |
| Testes frontend (Vitest) | `npm run test:unit -- --run` | ✅ 46 testes (12 arquivos) passando |
| Build de produção | `npm run build` | ✅ build concluído (12.31s) |

**Testes que não puderam ser executados:**

| Teste | Motivo | Risco |
|---|---|---|
| E2E (Playwright) | Não há `@playwright/test` no `package.json`, nem `playwright.config.ts` ou `frontend/e2e/`. A ADR 0005 declara a adoção, mas a implementação não existe. | Fluxo crítico (login → criar hidrômetro → ingestão → alerta → resolver) não é validado de ponta a ponta. |
| Teste de carga `/api/ingest` | Não há ferramenta/script de carga no projeto. | Não há evidência de comportamento sob volume real de sensores. |

---

## 6. Problemas encontrados

| ID | Problema | Severidade | Local | Bloqueia Merge? |
| -- | -------- | ---------- | ----- | --------------- |
| P1 | ADRs marcadas "Aceito" descrevem funcionalidades não implementadas (eventos de cache, logs JSON, CSP nonce, E2E) | ALTO | `docs/adr/0001..0005` | Sim |
| P2 | CSP ainda usa `unsafe-inline`/`unsafe-eval` (contradiz ADR 0004) | ALTO | `backend/app/Http/Middleware/SecurityHeaders.php:28` | Sim |
| P3 | E2E incompleto + artefatos de execução falha no working tree | ALTO | `frontend/test-results/` | Sim |
| P4 | Diretório `docs/` não versionado (README referencia arquivos inexistentes no repo) | MÉDIO | `docs/`, `README.md:275` | Não |
| P5 | Registro público sem verificação de e-mail/captcha | MÉDIO | `backend/app/Http/Controllers/AuthController.php:27` | Não |
| P6 | API key de ingestão única e não rotacionável | MÉDIO | `backend/app/Http/Middleware/EnsureValidApiKey.php` | Não |
| P7 | Token persistido em `localStorage` (risco XSS) | MÉDIO | `frontend/src/stores/auth.ts:17` | Não |
| P8 | Sem arquivos de pin de versão (`.nvmrc`/`.php-version`) | BAIXO | raiz do repo | Não |
| P9 | Frontend Dockerfile usa `npm run dev` (não é imagem de produção) | BAIXO | `frontend/Dockerfile:26` | Não |
| P10 | `docker-compose.yml` com senhas hardcoded (dev) | BAIXO | `docker-compose.yml` | Não |
| P11 | Ingestão síncrona (sem fila/job) | MÉDIO | `backend/app/Services/ReadingService.php` | Não |
| P12 | Tabela `readings` sem política de retenção/particionamento | MÉDIO | `backend/database/migrations/..._create_readings_table.php` | Não |
| P13 | Sem observabilidade (APM, `X-Request-Id`) | BAIXO | `backend/` | Não |
| P14 | `frontend/test-results/` não está no `.gitignore` | BAIXO | `.gitignore` | Não |

---

## 7. Análise detalhada dos problemas

### P1 — ADRs "Aceito" sem implementação correspondente (ALTO)

**O que está errado:** O diretório `docs/adr/` contém 5 ADRs com status "Aceito". Quatro delas descrevem decisões que **não foram implementadas** no código:

- **ADR 0001** (eventos para invalidação de cache): declara a criação de `App\Events\HydrometerCacheInvalidated` e `App\Listeners\InvalidateHydrometerCache`. Nenhum dos dois existe — a invalidação continua manual via `DashboardService::invalidateCache()`.
- **ADR 0003** (logs JSON em produção): declara `App\Logging\JsonLineFormatter` e canais `json`/`production` em `config/logging.php`. Nenhum existe.
- **ADR 0004** (CSP restritivo com nonce): declara `script-src 'self' 'nonce-{nonce}'`. O código real usa `'unsafe-inline' 'unsafe-eval'`.
- **ADR 0005** (testes E2E com Playwright): declara `@playwright/test`, `playwright.config.ts` e `frontend/e2e/`. Nenhum existe.

**Impacto:** Qualquer pessoa que leia a documentação acreditará que essas melhorias estão ativas, tomando decisões erradas (ex.: confiar em CSP restritivo que não existe, ou assumir cobertura E2E que não há). É uma inconsistência grave entre documentação e realidade.

**Por que bloqueia:** "Funcionalidades incompletas" + "inconsistência entre módulos" são critérios explícitos de bloqueio. A documentação afirma um estado que o código não entrega.

### P2 — CSP não é restritivo (ALTO)

**O que está errado:** `SecurityHeaders.php` define `script-src 'self' 'unsafe-inline' 'unsafe-eval'`, o que anula grande parte da proteção contra XSS. A ADR 0004 promete nonce, mas o código não o implementa.

**Impacto:** Se houver uma brecha de injeção, o atacante pode executar scripts inline/eval. Para uma aplicação de infraestrutura crítica, isso é relevante.

### P3 — E2E incompleto + artefatos de falha (ALTO)

**O que está errado:** Existe `frontend/test-results/` (não rastreado) contendo `error-context.md`, `test-failed-1.png` e `video.webm` de uma execução Playwright **falha** (`net::ERR_CONNECTION_REFUSED`). O arquivo `.last-run.json` registra `"status": "failed"`. Porém, o teste `e2e/fluxo-critico.spec.ts` referenciado **não existe mais** no repositório, e o Playwright não está no `package.json`.

**Impacto:** Indica que a implementação E2E foi iniciada e abandonada. O fluxo crítico do produto não tem validação de ponta a ponta, e os artefatos de falha poluem o working tree.

### P4 — `docs/` não versionado (MÉDIO)

**O que está errado:** O diretório `docs/` está listado em `.git/info/exclude` (configuração local, não commitada) e **não é rastreado** em nenhuma branch (`git ls-tree` de `develop` e `main` não retorna `docs/`). O `README.md:275` referencia `docs/auditoria-tecnica-pleno.md`, que **não existirá** para quem clonar o repositório.

**Impacto:** Documentação referenciada pelo README fica inacessível; o relatório de auditoria (que é a base do plano de produção) não acompanha o código.

### P5 — Registro público sem proteção (MÉDIO)

**O que está errado:** `AuthController::register` permite criação de conta sem verificação de e-mail, captcha ou aprovação. O rate limit (`throttle:auth`, 5/min por IP) mitiga parcialmente, mas não impede criação massiva.

**Impacto:** Enumeração de e-mails, criação de contas indevidas.

### P6 — API key única e não rotacionável (MÉDIO)

**O que está errado:** O endpoint `/api/ingest` usa uma única chave global (`INGEST_API_KEY`). Não há identificação por cliente, revogação granular ou rotação.

**Impacto:** Se a chave vazar, todos os dispositivos ficam comprometidos.

### P7 — Token em `localStorage` (MÉDIO)

**O que está errado:** `auth.ts` persiste o token Sanctum em `localStorage`, vulnerável a XSS. O CSP atual (com `unsafe-inline`/`unsafe-eval`) não oferece proteção suficiente.

**Impacto:** Exfiltração de token em caso de XSS.

### P8 — Sem pin de versão (BAIXO)

**O que está errado:** Não há `.nvmrc`, `.node-version` ou `.php-version`. O CI usa Node 22 e PHP 8.4, mas o `engines` do frontend aceita `^20.19 || >=22.12`, e o README já mencionou PHP 8.5 em versões anteriores.

**Impacto:** Divergência entre ambiente local e CI.

### P9 — Frontend Dockerfile dev-only (BAIXO)

**O que está errado:** `frontend/Dockerfile` roda `npm run dev` (Vite dev server). Não é imagem de produção (o frontend vai para a Vercel como estático).

**Impacto:** Baixo, pois o deploy real é via Vercel; mas o Dockerfile é enganoso.

### P10 — Senhas hardcoded no docker-compose (BAIXO)

**O que está errado:** `docker-compose.yml` usa `secret`/`root` como fallback. É ambiente de dev, mas deveria vir de `.env`.

**Impacto:** Baixo (dev), mas é má prática.

### P11 — Ingestão síncrona (MÉDIO)

**O que está errado:** Toda ingestão é síncrona. Alto volume pode bloquear workers PHP.

**Impacto:** Latência sob carga; risco de timeout. Roadmap, não urgente para o escopo atual.

### P12 — Sem retenção de leituras (MÉDIO)

**O que está errado:** A tabela `readings` cresce indefinidamente, sem arquivamento/particionamento.

**Impacto:** Queries de dashboard/export ficam lentas ao longo dos meses. Roadmap.

### P13 — Sem observabilidade (BAIXO)

**O que está errado:** Sem APM, métricas ou correlação por `X-Request-Id`.

**Impacto:** Dificuldade de diagnóstico em produção.

### P14 — `test-results/` não ignorado (BAIXO)

**O que está errado:** `frontend/test-results/` não está no `.gitignore`, então artefatos de teste aparecem como untracked.

**Impacto:** Poluição do `git status`; risco de commit acidental de artefatos.

---

## 8. Plano de implementação

> Ordenado por prioridade. As tarefas 1–3 são bloqueadoras; as demais são melhorias recomendadas.

### Tarefa 1 — Alinhar ADRs com a realidade do código (bloqueadora)

**Problema:** P1.

**O que fazer:** Para cada ADR "Aceito" sem implementação, decidir entre (a) implementar ou (b) rebaixar o status para "Proposto"/"Rejeitado" e documentar o motivo.

**Implementação:**
1. ADR 0001 (eventos de cache): ou criar `App\Events\HydrometerCacheInvalidated` + `App\Listeners\InvalidateHydrometerCache` e disparar nos pontos de mutação, ou marcar como "Proposto" (a invalidação manual atual funciona e é testada).
2. ADR 0003 (logs JSON): ou criar `App\Logging\JsonLineFormatter` + canais `json`/`production`, ou marcar como "Proposto".
3. ADR 0004 (CSP nonce): ver Tarefa 2.
4. ADR 0005 (E2E): ver Tarefa 3.

**Validação:** `git grep` não deve encontrar ADR "Aceito" cuja implementação não exista. Cada ADR deve refletir fielmente o código.

**Critério de conclusão:** Não há ADR "Aceito" descrevendo funcionalidade ausente no código.

---

### Tarefa 2 — Corrigir CSP (bloqueadora)

**Problema:** P2.

**O que fazer:** Remover `unsafe-inline`/`unsafe-eval` do `script-src` e adotar nonce, OU — se o nonce for inviável no build atual do Vue/Vite — documentar explicitamente na ADR 0004 que o CSP permanece com `unsafe-inline`/`unsafe-eval` e por quê.

**Implementação:**
1. Em `SecurityHeaders.php`, gerar um nonce por requisição e injetá-lo no `script-src`.
2. Garantir que o frontend (Vite) use o nonce nos scripts inline (ou confirmar que o build não gera scripts inline).
3. Se o nonce não for viável, reescrever a ADR 0004 para refletir o estado real e registrar a decisão de adiar.

**Validação:** `curl -I` em uma resposta deve mostrar `script-src` sem `unsafe-inline`/`unsafe-eval` (ou a ADR deve explicar a exceção). Testar que o frontend continua carregando.

**Critério de conclusão:** CSP e ADR 0004 estão consistentes entre si.

---

### Tarefa 3 — Resolver E2E (bloqueadora)

**Problema:** P3.

**O que fazer:** Decidir entre implementar o E2E (conforme ADR 0005) ou remover a ADR 0005 e os artefatos de falha.

**Implementação:**
1. Se implementar: adicionar `@playwright/test` ao `frontend/package.json`, criar `playwright.config.ts` e `frontend/e2e/fluxo-critico.spec.ts`, e adicionar `test:e2e` ao script.
2. Se adiar: remover `frontend/test-results/`, adicionar `frontend/test-results/` ao `.gitignore`, e rebaixar a ADR 0005 para "Proposto".

**Validação:** Se implementado, `npm run test:e2e` deve passar com backend+frontend rodando. Se adiado, `git status` deve ficar limpo e a ADR deve refletir o adiamento.

**Critério de conclusão:** Não há artefatos de E2E falho no working tree, e a ADR 0005 reflete fielmente o estado (implementado ou adiado).

---

### Tarefa 4 — Versionar o diretório `docs/` (recomendada)

**Problema:** P4.

**O que fazer:** Remover `docs/` de `.git/info/exclude` e versionar os arquivos de documentação (incluindo este plano).

**Implementação:**
1. Remover a linha `docs/` de `.git/info/exclude`.
2. `git add docs/` e confirmar que `docs/auditoria-tecnica-pleno.md` e os ADRs passam a ser rastreados.

**Validação:** `git ls-files docs/` deve listar os arquivos. O link do README deve apontar para um arquivo existente no repo.

**Critério de conclusão:** `docs/` está versionado e o README não referencia arquivos inexistentes.

---

### Tarefa 5 — Proteger registro público (recomendada)

**Problema:** P5.

**O que fazer:** Desabilitar registro público em produção ou exigir verificação de e-mail/captcha.

**Implementação:** Adicionar flag de configuração (ex.: `REGISTRATION_ENABLED`) e retornar 403 quando desabilitada em produção; ou implementar `MustVerifyEmail`.

**Validação:** Teste de Feature confirmando que o registro é bloqueado quando a flag está desligada.

**Critério de conclusão:** Registro público não é possível em produção sem verificação.

---

### Tarefa 6 — Rotação de API key (recomendada)

**Problema:** P6.

**O que fazer:** Introduzir tabela `ingest_clients` com chave por cliente e escopo de dispositivos.

**Implementação:** Migration + model + middleware que valida a chave contra a tabela (mantendo `hash_equals`).

**Validação:** Teste de Feature para chave válida/inválida/revogada.

**Critério de conclusão:** É possível rotacionar/revogar chave por cliente sem afetar os demais.

---

### Tarefa 7 — Migrar token para cookie HttpOnly (recomendada)

**Problema:** P7.

**O que fazer:** Migrar de `localStorage` para cookie `HttpOnly` (Sanctum SPA) ou sessionStorage + refresh rotation.

**Implementação:** Ajustar `auth.ts`, `api.ts` e `config/cors.php` (`supports_credentials => true`).

**Validação:** Testes de store confirmando que o token não fica acessível via JS.

**Critério de conclusão:** Token não é persistido em `localStorage`.

---

### Tarefa 8 — Adicionar pin de versão (opcional)

**Problema:** P8.

**O que fazer:** Criar `.nvmrc` (Node 22) e `.php-version` (8.4).

**Validação:** CI e ambiente local usam a mesma versão.

---

### Tarefa 9 — Corrigir Dockerfiles/compose (opcional)

**Problema:** P9, P10.

**O que fazer:** Frontend Dockerfile multi-stage (nginx) ou documentar como dev-only; externalizar credenciais do compose.

---

### Tarefa 10 — Fila assíncrona + retenção + observabilidade (roadmap)

**Problema:** P11, P12, P13.

**O que fazer:** Introduzir `IngestReadingJob`, política de retenção de `readings`, e `X-Request-Id`/APM. Não bloqueiam o merge; entram no backlog.

---

## 9. Testes necessários

1. **E2E do fluxo crítico** (login → criar hidrômetro → ingestão zerada → alerta → resolver → logout) — hoje ausente (ADR 0005 não implementada).
2. **Teste de carga `/api/ingest`** — ausente; sem requisito de escala definido, mas recomendado antes de produção real.
3. **Teste de registro desabilitado** (se a Tarefa 5 for implementada).
4. **Teste de rotação/revogação de API key** (se a Tarefa 6 for implementada).

Os testes unitários/integração existentes (114 backend + 46 frontend) já cobrem bem as regras de negócio críticas (deduplicação de alertas, watchdog, RBAC, cache, auth).

---

## 10. Checklist de pré-merge

- [x] Build funcionando
- [x] Type checking passando
- [x] Lint passando
- [x] Testes passando (114 backend + 46 frontend)
- [x] Migrations validadas (sem breaking change)
- [x] Funcionalidades críticas validadas (dedup, watchdog, RBAC, cache)
- [x] Sem regressões conhecidas
- [ ] ADRs alinhadas com o código (Tarefa 1)
- [ ] CSP consistente com a ADR 0004 (Tarefa 2)
- [ ] E2E resolvido (implementado ou adiado) (Tarefa 3)
- [ ] `docs/` versionado (Tarefa 4)
- [ ] Variáveis de ambiente verificadas (`.env.example` completo)
- [ ] CI/CD validado (ci.yml + deploy.yml)

---

## 11. Riscos residuais

- **Segurança:** CSP ainda permissivo, registro público, API key única e token em `localStorage` permanecem como débito até as tarefas 2/5/6/7 serem concluídas.
- **Escala:** ingestão síncrona e tabela `readings` sem retenção podem degradar com volume real.
- **Observabilidade:** sem APM/request-id, diagnóstico em produção é limitado.
- **E2E:** fluxo crítico sem validação de ponta a ponta.

Esses riscos **não impedem** o uso como demonstração/portfolio, mas devem ser endereçados antes de produção real (conforme o próprio README já orienta).

---

## 12. Critério final para merge

`develop` será considerada **pronta para `main`** quando **todas** as condições abaixo forem verdadeiras:

1. Nenhuma ADR marcada "Aceito" descreve funcionalidade ausente no código (Tarefa 1 concluída).
2. O CSP e a ADR 0004 estão consistentes entre si (Tarefa 2 concluída).
3. Não há artefatos de E2E falho no working tree, e a ADR 0005 reflete o estado real (Tarefa 3 concluída).
4. `docs/` está versionado e o README não referencia arquivos inexistentes (Tarefa 4 concluída).
5. Build, type-check, lint e testes continuam passando após as correções.

As tarefas 5–10 são recomendadas, mas **não bloqueiam** o merge — podem ser tratadas como backlog pós-merge.
