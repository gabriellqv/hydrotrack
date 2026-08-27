# ADR 0007: Armazenamento do Token de Autenticacao

## Status

Aceito

## Contexto

O HydroTrack utiliza Laravel Sanctum com tokens pessoais (`plainTextToken`) para autenticar a SPA Vue.js. O token precisa ser persistido no frontend para manter a sessao entre navegacoes e recarregamentos de pagina.

## Decisao

Manter o token em `localStorage` (`auth_token`), conforme implementado em `frontend/src/stores/auth.ts`.

Esta escolha foi feita por simplicidade no escopo atual do portfolio:

1. A SPA e recarregada no browser e precisa restaurar a sessao sem cookie-based authentication.
2. O uso de `localStorage` e compativel com o fluxo stateless de Sanctum via `Authorization: Bearer`.
3. Nao exige configuracao adicional de CORS com credenciais (`supports_credentials = false`) nem de cookie `HttpOnly`.

## Consequencias

- **Beneficios:**
  - Implementacao simples e compativel com a arquitetura stateless atual.
  - Facilidade de testes e debug local.
- **Riscos:**
  - `localStorage` e acessivel via JavaScript. Se houver brecha XSS, o token pode ser exfiltrado.
  - Nao e recomendado para aplicacoes financeiras ou de alta sensibilidade sem mitigacoes adicionais.
- **Melhorias futuras:**
  - Migrar para cookie `HttpOnly` com Sanctum SPA authentication e `supports_credentials = true`.
  - Implementar refresh token rotation.
- **Alternativas rejeitadas:**
  - `sessionStorage` — rejeitado porque perderia a sessao ao fechar a aba, prejudicando a UX do dashboard.
  - Cookie `HttpOnly` imediato — rejeitado por aumentar a complexidade do setup de CORS e autenticacao sem ganho critico para o escopo de portfolio.
