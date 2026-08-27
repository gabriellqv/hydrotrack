# ADR 0005: Testes E2E com Playwright

## Status

Aceito

## Contexto

O HydroTrack possui um fluxo critico que envolve multiplas camadas: autenticacao, CRUD de hidrometros, ingestao M2M de leituras e geracao de alertas. Testes unitarios e de integracao cobrem servicos e controllers isoladamente, mas nao garantem que o fluxo completo funcione do ponto de vista do usuario final.

Precisavamos de uma ferramenta de testes end-to-end (E2E) robusta, moderna e com suporte nativo a multiplos navegadores.

## Decisao

Adotar o **Playwright** como ferramenta de testes E2E:

1. **Pacote:** `@playwright/test` instalado no frontend.
2. **Configuracao:** `frontend/playwright.config.ts` com:
   - `testDir: './e2e'`.
   - Projeto Chromium (Firefox e WebKit podem ser adicionados futuramente).
   - `baseURL` lido de variaveis de ambiente (`PLAYWRIGHT_BASE_URL`).
   - `webServer` opcional usando `npm run preview`.
3. **Variaveis de ambiente:** carregadas de `.env.local` e `.env.e2e`.
4. **Suite inicial:** `frontend/e2e/fluxo-critico.spec.ts` cobre:
   - Login como administrador.
   - Criacao de hidrometro.
   - Envio de leitura zerada via API M2M e geracao de alerta.
   - Exclusao do hidrometro.
   - Logout.

O Playwright se integra ao pipeline CI e pode ser executado localmente com `npm run test:e2e`.

## Consequencias

- **Beneficios:**
  - Testes E2E reais em navegadores (Chromium; Firefox e WebKit em roadmap).
  - API de request do Playwright permite simular o dispositivo IoT sem depender de hardware.
  - Retries, traces e screenshots automaticos facilitam a depuracao de falhas intermitentes.
- **Riscos:**
  - Testes E2E sao mais lentos e frageis que testes unitarios. Devem ser mantidos enxutos e focados em fluxos criticos.
  - Requerem ambiente com backend e frontend disponiveis, o que pode complicar a execucao local.
- **Alternativas rejeitadas:**
  - Cypress — rejeitado por preferir a arquitetura moderna e suporte nativo a multiplos navegadores do Playwright.
  - Vitest Browser Mode — rejeitado por ainda estar em evolucao e nao cobrir multiplos navegadores tao maduramente quanto o Playwright.
