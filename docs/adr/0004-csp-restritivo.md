# ADR 0004: Content Security Policy (CSP)

## Status

Aceito (com excecao documentada)

## Contexto

O HydroTrack e uma aplicacao web exposta a internet com dados de infraestrutura critica (telemetria hidrica). Como medida de defesa em profundidade, decidimos aplicar headers de seguranca em todas as respostas da API e do frontend, com destaque para o Content Security Policy (CSP).

O desafio e que o frontend Vue.js injeta scripts e estilos dinamicamente durante o desenvolvimento (Vite dev server) e em producao (build), enquanto o mapa interativo carrega tiles de dominios externos (`*.tile.openstreetmap.org`).

## Decisao

Aplicar um CSP via middleware `App\Http\Middleware\SecurityHeaders`, mantendo a politica compativel com o build atual do Vue/Vite:

```text
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: blob: https://*.tile.openstreetmap.org;
font-src 'self';
connect-src 'self';
frame-ancestors 'none';
base-uri 'self';
form-action 'self';
```

Pontos importantes:

1. **`unsafe-inline` e `unsafe-eval` em scripts:** permitidos porque o build atual do Vue/Vite ainda gera scripts inline e uso de eval em alguns pontos. A remocao dessas diretivas sera abordada em melhoria futura com nonce por requisicao.
2. **`unsafe-inline` para estilos:** permitido para estilos inline, pois o Vue.js e bibliotecas de componentes frequentemente os utilizam. Pode ser revisado no futuro com hashes especificas.
3. **Imagens externas:** permitidas apenas para tiles do OpenStreetMap, necessarios para o mapa.
4. **Fontes:** restritas a origem propria. Google Fonts nao e utilizado no projeto.
5. **Connect:** limitado a `self`, garantindo que o frontend so converse com a propria origem ou API configurada no mesmo dominio via proxy.
6. **Frame-ancestors:** `none` para prevenir clickjacking.

Alem do CSP, o middleware adiciona:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` em producao.

## Consequencias

- **Beneficios:**
  - Reducao da superficie de ataque XSS e clickjacking pelos headers auxiliares e CSP basico.
  - CSP audivel e facil de ajustar em um unico ponto.
  - Headers padronizados em todas as respostas da API.
- **Riscos:**
  - As diretivas `unsafe-inline` e `unsafe-eval` no `script-src` reduzem a eficacia do CSP contra XSS. Recomenda-se futura migracao para nonce por requisicao.
  - CSP muito restritivo pode bloquear recursos legitimos. Deve ser testado apos qualquer mudanca de dependencia frontend.
- **Melhorias futuras:**
  - Remover `unsafe-inline`/`unsafe-eval` do `script-src` e adotar nonce gerado por requisicao.
- **Alternativas rejeitadas:**
  - CSP totalmente permissivo (`default-src *`) — rejeitado por nao agregar protecao.
  - Configurar CSP apenas no servidor web (Nginx/Apache) — rejeitado para manter a politica versionada junto ao codigo e aplicavel em todos os ambientes.
