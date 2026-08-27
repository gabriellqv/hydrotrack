# ADR 0008: Autenticacao M2M via API Key

## Status

Aceito

## Contexto

O endpoint `POST /api/ingest` recebe leituras dos sensores (ou do simulador IoT). Ele precisa de uma autenticacao simples e robusta, independente do fluxo de login de usuarios via Sanctum.

## Decisao

Utilizar uma unica API Key global configurada via `INGEST_API_KEY` (`services.ingest.api_key`).

O middleware `EnsureValidApiKey` valida o header `X-API-Key` usando `hash_equals()` para prevenir timing attacks.

Esta escolha e adequada para o escopo atual do portfolio:

1. O simulador IoT e o unico cliente M2M no momento.
2. A chave e configurada por ambiente, nao commitada.
3. O rate limit (`throttle:60,1`) protege contra uso abusivo.

## Consequencias

- **Beneficios:**
  - Implementacao simples e de facil auditoria.
  - Compativel com simulador e sensores sem fluxo OAuth complexo.
- **Riscos:**
  - Chave unica e compartilhada: se vazar, todos os dispositivos ficam comprometidos.
  - Nao ha revogacao granular por cliente/dispositivo.
- **Melhorias futuras:**
  - Criar tabela `ingest_clients` com chave por cliente, escopo de dispositivos permitidos e revogacao.
  - Adicionar rate limit por cliente em vez de global.
- **Alternativas rejeitadas:**
  - OAuth 2.0 / mTLS — rejeitado por complexidade desproporcional ao escopo atual.
