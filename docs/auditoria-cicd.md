# Auditoria de CI/CD — HydroTrack

**Data:** 21/08/2026
**Escopo:** workflow CI (GitHub Actions), Dockerfiles, docker-compose, estratégia de branches, deploy, segurança, performance e observabilidade.

---

## 1. Resumo Executivo

**Estado atual:** O projeto tem **apenas CI** (um único workflow `.github/workflows/ci.yml` com 2 jobs: backend e frontend). **Não existe CD automatizado** — o deploy é feito manualmente nos dashboards do Render (backend) e Vercel (frontend), sem pipeline, sem rollback, sem health check pós-deploy.

**Principais problemas:**

1. **Backend roda `php artisan serve` em produção** (servidor embutido single-threaded do PHP) — inadequado para produção.
2. **`migrate --force` roda no `CMD` do container** — migration executada a cada start, sem rollback, sem separação do deploy.
3. **Zero automação de deploy** — sem build de imagem, sem registry, sem rollback, sem smoke test.
4. **Inconsistência de versões** — PHP 8.4 (CI/Dockerfile/composer) vs 8.5 (README/env); Node 22 (CI) vs engines `^20.19 || >=22.12`.
5. **Segurança do workflow** — sem `permissions:`, sem pin de SHA nas actions, sem scan de dependências/secrets.

**Principais riscos:**

- Deploy manual sem rollback → produção pode ficar inconsistente.
- Migration irreversível rodando no boot do container.
- Servidor PHP embutido → indisponibilidade sob carga.
- Secrets de produção acessíveis sem controle (sem environments no GitHub).

**Nível de maturidade:** Básico. CI funcional e bem organizado, mas CD inexistente e segurança de pipeline ausente.

**Nota: 4/10.**

---

## 2. Arquitetura Atual do CI/CD

```
Developer → commit → husky (lint-staged + commitlint) → push
   ↓
GitHub Actions (ci.yml) — dispara em push e PR para develop/main
   ├─ Job backend: checkout → setup-php 8.4 → cache composer → install → .env → key:generate → Pint → Pest
   └─ Job frontend: checkout → setup-node 22 → npm ci → Prettier → ESLint → type-check → vitest → build
   ↓
Merge manual (sem branch protection configurada no repo)
   ↓
Deploy MANUAL:
   ├─ Backend → Render (Dockerfile, php artisan serve, migrate no boot)
   ├─ Frontend → Vercel (SPA estática)
   └─ DB → TiDB Serverless (MySQL-compatível)
```

**Não há:** release workflow, build de imagem, push para registry, deploy automatizado, rollback, health check, smoke test, Dependabot, CODEOWNERS, environments do GitHub.

---

## 3. Problemas Encontrados

| Problema | Severidade | Impacto | Causa | Solução |
| -------- | ---------- | ------- | ----- | ------- |
| `php artisan serve` em produção | P0 | Indisponibilidade sob carga; single-threaded | Dockerfile usa servidor embutido | php-fpm + nginx (ou Octane) |
| `migrate --force` no `CMD` do container | P0 | Migration irreversível no boot; deploy pode quebrar sem rollback | Entrypoint executa migrate a cada start | Separar migration do start; job de deploy dedicado |
| Sem CD/deploy automatizado | P0 | Deploy manual, sem rollback, sem verificação | Nunca implementado | Workflow de deploy com health check + rollback |
| Sem `permissions:` no workflow | P1 | GITHUB_TOKEN com permissões excessivas | Default do GitHub | `permissions: contents: read` |
| Actions sem pin de SHA | P1 | Supply-chain risk | `@v4`/`@v2` flutuantes | Fixar em SHA (ou tag + commit) |
| Inconsistência PHP 8.4 vs 8.5 | P1 | CI testa versão diferente da produção | README/env desatualizados | Padronizar em 8.4 (composer exige ^8.4) |
| Sem scan de dependências/secrets | P1 | Vulnerabilidades não detectadas | Nunca configurado | Dependabot + CodeQL (ou equivalente) |
| Sem `concurrency` | P2 | Deploy/CI concorrente pode corromper estado | Ausente | `concurrency.group` + `cancel-in-progress` |
| Sem timeout nos jobs | P2 | Job pendurado consome minutos | Ausente | `timeout-minutes` |
| CI roda em push + PR (duplicado) | P2 | Execuções redundantes | `on.push` + `on.pull_request` | Manter PR; push só em main/develop |
| Sem `paths-ignore` (monorepo) | P2 | Mudança no backend dispara job frontend e vice-versa | Ausente | `paths`/`paths-ignore` por job |
| Sem `.nvmrc`/`.node-version`/`.php-version` | P2 | Versão local ≠ CI | Ausente | Adicionar arquivos de pin |
| Sem artifact/coverage | P2 | Sem visibilidade de build/cobertura | Ausente | Upload de artifact + coverage report |
| Frontend Dockerfile usa `npm run dev` | P2 | Imagem não é de produção (mas frontend vai pra Vercel) | Dockerfile só p/ dev | Multi-stage com nginx (ou documentar como dev-only) |
| `docker-compose.yml` com senhas hardcoded | P2 | Credenciais fracas (dev) | `secret`/`root` | Usar `.env` + override |
| Comentário duplicado no backend Dockerfile | P3 | Cosmético | Copy-paste | Remover |
| Sem CODEOWNERS | P3 | Sem revisão obrigatória | Ausente | Adicionar |

---

## 4. Melhorias Recomendadas (P0 → P3)

### P0 — Crítico

1. **Substituir `php artisan serve` por php-fpm + nginx** no backend Dockerfile (ou documentar que Render usa outro mecanismo). O servidor embutido não é para produção.
2. **Separar migration do boot do container.** Migrations devem rodar como passo explícito do deploy, não no `CMD`.
3. **Criar pipeline de deploy** com build de imagem → push → deploy → health check → smoke test → rollback.

### P1 — Alto

4. Adicionar `permissions: contents: read` (e `id-token: write` só onde necessário).
5. Fixar actions em SHA.
6. Padronizar versões (PHP 8.4, Node 22) e adicionar arquivos de pin.
7. Adicionar Dependabot (security updates) + CodeQL (SAST).

### P2 — Médio

8. Adicionar `concurrency` e `timeout-minutes`.
9. Adicionar `paths`/`paths-ignore` para jobs do monorepo.
10. Upload de artifact do build + coverage report.
11. Corrigir frontend Dockerfile (multi-stage nginx) ou documentar como dev-only.
12. Externalizar credenciais do docker-compose.

### P3 — Baixo

13. Remover comentário duplicado.
14. Adicionar CODEOWNERS.

---

## 5. Pipeline Recomendado

```
Developer → PR
   ↓
CI (por job, com paths-ignore):
   ├─ backend: Pint → Pest (com coverage)
   └─ frontend: Prettier → ESLint → type-check → Vitest → build
   ↓
Security (CodeQL + Dependabot) — paralelo
   ↓
Review + merge (branch protection: CI obrigatório)
   ↓
Deploy (workflow separado, disparado por push em main):
   ├─ Build imagem backend (multi-stage, cache BuildKit)
   ├─ Push para registry (GHCR)
   ├─ Deploy (Render/VPS)
   ├─ Migration (passo explícito, com backup)
   ├─ Health check (/up)
   ├─ Smoke test (login + endpoint crítico)
   └─ Rollback automático se falhar
```

**Frontend (Vercel):** já é estático; o deploy pode continuar via integração nativa do Vercel, mas com um smoke test pós-deploy.

---

## 6. Segurança

**Vulnerabilidades encontradas:**

- GITHUB_TOKEN com permissões default (excessivas).
- Actions não fixadas em SHA (supply-chain).
- Sem secret scanning / dependency scanning.
- `docker-compose.yml` com senhas hardcoded (dev).
- Backend Dockerfile roda como `root` (imagem `php:8.4-cli-alpine` sem `USER`).

**Melhorias:**

- `permissions: contents: read` no workflow.
- Fixar actions em SHA.
- Dependabot (security updates) — **custo baixo, benefício alto**.
- CodeQL (SAST) — **recomendado** para PHP/TS; custo baixo (gratuito em repo público).
- Secret scanning (nativo do GitHub) — **gratuito, habilitar**.
- Container scanning (Trivy/Grype) — **opcional**; só se o backend for de fato containerizado em produção.
- SBOM / image signing — **não recomendo** para este porte (complexidade desproporcional).

---

## 7. Performance

**Gargalos atuais:**

- Jobs backend e frontend rodam **sequencialmente** (poderiam ser paralelos — já são, pois são jobs independentes; o problema é que **não há `paths-ignore`**, então ambos rodam sempre).
- `composer install` sem `--no-progress` (menor).
- Sem cache de Docker layers (não há build de imagem no CI hoje).

**Otimizações:**

| Antes | Depois | Ganho esperado |
| ----- | ------ | -------------- |
| Ambos jobs rodam em todo push/PR | `paths-ignore` por job | ~50% menos execuções em PRs de um só lado |
| Sem `concurrency` | `concurrency.group` + cancel | Evita fila de jobs obsoletos |
| Sem timeout | `timeout-minutes: 10` | Evita jobs pendurados |
| Cache composer só por lockfile | Manter (já correto) | — |
| Sem cache npm explícito | `setup-node cache: npm` (já existe) | — |
| Build sem artifact | Upload artifact | Reuso em deploy |

**Estimativa:** com `paths-ignore` + `concurrency`, o tempo médio de feedback cai ~30–40% em PRs típicos.

---

## 8. Deploy

**Avaliação atual:** build → deploy → migration → health check → smoke test → rollback **não existem como pipeline**. O deploy é manual.

**Riscos concretos:**

- **Deploy parcial:** backend e frontend deployados em momentos diferentes, sem coordenação.
- **Migration irreversível:** `migrate --force` no boot, sem backup.
- **Sem rollback:** se quebrar, não há como voltar automaticamente.
- **Sem health check:** deploy "bem-sucedido" = exit code 0, sem verificar se o serviço responde.
- **Imagem `latest`:** não há versionamento de imagem (não há imagem).

**Recomendação (simples e segura):**

1. Versionar imagem por commit SHA (`ghcr.io/.../hydrotrack-api:${{ github.sha }}`).
2. Deploy com health check no `/up` (já existe).
3. Smoke test: login + `GET /api/dashboard/summary`.
4. Rollback: manter a imagem anterior taggeada (`:previous`) e re-deployar em caso de falha.
5. Migration como passo explícito, com `--pretend`/backup antes.

---

## 9. Plano de Implementação

### Fase 1 — Correções críticas (P0)

1. Corrigir backend Dockerfile (php-fpm + nginx, ou documentar o mecanismo real do Render).
2. Separar migration do boot do container.
3. Criar workflow de deploy com health check + rollback.

### Fase 2 — Confiabilidade (P1/P2)

4. `permissions`, `concurrency`, `timeout-minutes`, `paths-ignore`.
5. Padronizar versões + arquivos de pin (`.nvmrc`, `.php-version`).
6. Upload de artifact + coverage.

### Fase 3 — Segurança (P1)

7. Fixar actions em SHA.
8. Dependabot + CodeQL + secret scanning.

### Fase 4 — Performance (P2)

9. BuildKit + layer caching no build de imagem.
10. Reuso de artifact entre CI e deploy.

### Fase 5 — Observabilidade (P2)

11. Health check + smoke test no deploy.
12. Logs/alertas de falha de deploy.

### Fase 6 — Melhorias avançadas (P3)

13. CODEOWNERS, limpeza de comentários, externalizar credenciais do compose.

---

## 10. Decisões que precisam de definição

Antes de implementar, é necessário confirmar pontos que **não estão no código**:

1. **Onde o backend realmente roda em produção?** O Dockerfile sugere Render, mas o `php artisan serve` indica que pode não estar usando o Dockerfile de fato. É preciso saber o mecanismo real.
2. **O frontend é 100% estático no Vercel?** Se sim, o `frontend/Dockerfile` é só para dev local e não precisa de correção de produção.
3. **Há orçamento/necessidade de registry?** GHCR é gratuito para repo público — recomendado.
4. **A estratégia de branches atual (main + develop) deve ser mantida?** Para um projeto deste porte, **GitHub Flow (trunk-based)** seria mais simples que o Git Flow atual — mas é uma decisão de time.

---

## Conclusão

O CI está funcional e razoavelmente organizado, mas o **CD é inexistente** e há **riscos P0 de produção** (servidor PHP embutido + migration no boot). A prioridade é: (1) corrigir o Dockerfile de produção, (2) separar migration do boot, (3) criar um deploy mínimo com health check e rollback. Segurança (permissions, pin de SHA, Dependabot) vem logo em seguida, com custo baixo e benefício alto.
