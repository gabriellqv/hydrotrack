# Arquitetura do HydroTrack

Este documento descreve a arquitetura de alto nivel do HydroTrack, com foco no frontend.

## Visao geral

O HydroTrack e uma aplicacao web monolitica dividida em duas camadas principais:

- **Backend (Laravel):** expoe uma API REST protegida por Sanctum e executa a logica de negocio em Services.
- **Frontend (Vue.js 3):** SPA responsiva que consome a API, gerencia estado com Pinia e renderiza dashboards, mapas e alertas.

## Frontend

### Estrutura de diretorios

```
frontend/src/
  components/        # Componentes reutilizaveis (ui/, MapView, graficos, alertas)
  views/             # Paginas roteadas (Login, Dashboard, Hidrometros, etc.)
  stores/            # Pinia stores (auth, hydrometer, dashboard, alert, toast)
  services/          # Cliente HTTP centralizado (Axios) e classe ApiError
  router/            # Configuracao de rotas e guards de autenticacao
  types/             # Interfaces TypeScript compartilhadas
  constants/         # Constantes globais (periodos, labels de tipo/status)
  composables/       # Logica reutilizavel entre componentes
```

### Fluxo de dados

1. O usuario interage com uma **View** ou **Componente**.
2. A View/Componente delega operacoes de estado a uma **Store** Pinia.
3. A Store chama a API via `services/api.ts`.
4. A API retorna dados que sao normalizados e armazenados reativamente.
5. Componentes derivam a UI a partir do estado reativo.

### Gerenciamento de estado

Cada dominio possui uma store isolada:

- `auth`: token Sanctum, usuario logado, logout.
- `hydrometer`: listagem paginada, criacao, edicao, exclusao e detalhes.
- `dashboard`: resumo, consumo, mapa e alertas recentes.
- `alert`: listagem e resolucao de alertas.
- `toast`: notificacoes flutuantes globais.

### Roteamento e autenticacao

O Vue Router utiliza guards para proteger rotas administrativas. O token e injetado automaticamente em todas as requisicoes pelo interceptor do Axios.

### Testes

A suite de testes do frontend utiliza Vitest e Vue Test Utils:

- Stores sao testadas com mocks do cliente Axios.
- Componentes sao testados com `mount` e slots quando necessario.
- Modais que usam `<Teleport>` precisam ser renderizados com `attachTo: document.body`.

## Backend

A API segue o padrao Controller magro + Service:

- Controllers recebem requests validadas e delegam a Services.
- Services contem a logica de negocio e regras de dominio.
- Form Requests isolam a validacao de entrada.
- API Resources formatam a saida JSON.

## Decisoes tecnicas

- **Pinia em vez de Vuex:** API mais simples, melhor suporte a TypeScript e Composition API.
- **Axios centralizado:** interceptadores unicos para token e tratamento de erros.
- **Services isolados:** facilita testes unitarios e mocks.
- **Leaflet em vez de Google Maps:** nao requer API key e e open source.
- **Tailwind CSS:** utilitario para manter consistencia visual sem CSS customizado excessive.
