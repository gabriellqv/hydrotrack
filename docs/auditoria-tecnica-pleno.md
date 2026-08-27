# Auditoria Tecnica HydroTrack

> Data: 2026-08-24
> Versao auditada: v1.0.0
> Escopo: backend Laravel 13 + frontend Vue.js 3 + infraestrutura Docker

## 1. Resumo Executivo

O HydroTrack e uma plataforma de telemetria hidrica IoT bem estruturada para um projeto de portfolio. A arquitetura adota separacao Controller/Service/Resource, validacao via Form Requests, autenticacao Sanctum + RBAC simples, simulador IoT via Artisan, dashboard com cache e documentacao OpenAPI via Scribe. Os testes automatizados passam (101 no backend, 41 no frontend) e a base de codigo nao apresenta bugs criticos evidentes.

Principais riscos observados estao em seguranca (token sem expiracao configurada, CORS sem credenciais, seeder expoe senhas no terminal), observabilidade (sem health check real, sem metricas), devops (sem CI/CD no repositorio, entrypoint Docker incompleto) e cobertura de testes (faltam testes de contrato, carga, E2E, testes de alguns componentes Vue e algumas rotas). A qualidade de codigo esta acima da media para um desenvolvedor junior, mas ainda distante do padrao pleno em ambientes reais.

**Pontos fortes:**

- Organizacao clara em Services, Controllers magros, Resources e Stores.
- Uso de cache com invalidacao event-driven.
- Testes automatizados passando com boa cobertura de funcionalidade.
- Tratamento padronizado de erros HTTP e headers de seguranca.
- Tipagem TypeScript presente em grande parte do frontend.

**Pontos fracos criticos:**

- Autenticacao stateless sem expiracao de token no codigo (24h via Sanctum config, mas tokens anteriores sao deletados no login).
- Registro publico sem verificacao de email nem recaptcha, permitindo enumeracao de usuarios.
- Seeder gera senhas administrativas aleatorias e as imprime no terminal.
- Frontend nao trata erros de rede sem response (ex.: offline).
- Alertas de leitura zerada/alto consumo sao recriados sem deduplicacao.
- Cache do dashboard nao e invalidado ao excluir hidrometro.
- Health check `/up` nao verifica conexao com banco.

**Nota geral: 6.5/10** — projeto funcional, apresentavel e com boas praticas iniciais, mas precisa de endurecimento de seguranca, observabilidade, CI/CD e ampliacao de testes antes de ser considerado pronto para producao real.

## 2. Arquitetura

### 2.1 Stack e organizacao

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 13, PHP 8.4, Eloquent, MySQL 8, Sanctum |
| Frontend | Vue 3, TypeScript, Pinia, Vue Router, Tailwind 4, Vite |
| Mapas/Graficos | Leaflet, vue-chartjs/Chart.js |
| Testes | Pest PHP, Vitest + Vue Test Utils |
| DevOps | Docker Compose, GitHub Actions |
| Qualidade | Laravel Pint, ESLint + Oxlint + Prettier, Husky + lint-staged |

### 2.2 Fluxo de dados

Cliente Vue -> API REST Laravel (Sanctum ou API key) -> MySQL. Simulador IoT e Watchdog acessam a mesma API/containers. Cache padrao: `database`.

### 2.3 Pontos positivos

- Controllers delegam para Services.
- Form Requests isolam validacao.
- Resources definem contrato JSON de saida.
- Models pequenas e com casts/relacoes bem definidos.
- Cache-aside no DashboardService.
- Vue usa stores Pinia isoladas com tipagem.

### 2.4 Problemas arquiteturais identificados

**P1 - Cache compartilhado e invalidacao manual**

- Arquivo: `backend/app/Services/DashboardService.php`
- Problema: invalidacao de cache e feita manualmente chamando `DashboardService::invalidateCache()` em pontos especificos (`ReadingService`, `AlertService`, `WatchdogCommand`). Esquecer um ponto de invalidacao leva a dados desatualizados.
- Impacto: risco de inconsistencia entre dashboard e estado real do banco.
- Severidade: P1 (alto).
- Como melhorar: usar Eventos/Listeners do Laravel (`ReadingCreated`, `AlertResolved`, `HydrometerWentOffline`) para invalidar caches ou, melhor ainda, adotar cache tags quando o driver permitir.
- Vale a pena alterar agora: sim, e baixo esforco e aumenta confiabilidade.

**P2 - API key de ingestao unica e compartilhada**

- Arquivo: `backend/app/Http/Middleware/EnsureValidApiKey.php`, `backend/config/services.php`
- Problema: o endpoint `/api/ingest` usa uma unica chave global (`INGEST_API_KEY`). Nao ha identificacao de origem, rate limit por cliente nem revogacao granular.
- Impacto: se a chave vazar, todos os dispositivos sao comprometidos; nao e possivel rotacionar chave por cliente.
- Severidade: P1 (alto).
- Como melhorar: tabela `ingest_clients` com chave unica por cliente, escopo de dispositivos permitidos, rate limit por cliente e auditoria de IPs.
- Vale a pena alterar agora: sim, se houver previsao de multiplos clientes/integradores; caso contrario documentar e mitigar com network policies.

**P3 - Ausencia de fila/job para processamento assincrono**

- Arquivo: `backend/app/Services/ReadingService.php`
- Problema: toda ingestao e sincrona. Alto volume de leituras pode bloquear workers PHP.
- Impacto: latencia alta sob carga, risco de timeout.
- Severidade: P2 (medio).
- Como melhorar: introduzir `IngestReadingJob` para persistencia e geracao de alertas em fila.
- Vale a pena alterar agora: nao e urgente para escopo atual, mas deve estar no roadmap.

## 3. Codigo

### 3.1 Backend

**P0 - Alertas sao recriados a cada ingestao zerada/alta sem deduplicacao**

- Arquivo: `backend/app/Services/ReadingService.php`, linhas 82-125.
- Problema: cada leitura com `value_m3 == 0` ou `> 10` cria um novo registro na tabela `alerts`. Nao ha controle de alerta aberto existente.
- Impacto: spam de alertas para o mesmo hidrometro; operador precisa resolver dezenas de alertas identicos.
- Severidade: P0 (critico).
- Como melhorar: antes de criar, verificar se ja existe alerta do mesmo tipo para o mesmo hidrometro com `resolved = false`. Se existir, atualizar `message`/timestamp ou simplesmente nao criar duplicado.
- Vale a pena alterar agora: sim, impacta diretamente a usabilidade e integridade dos dados.

**P1 - Comparacao de ponto flutuante com `== 0.0`**

- Arquivo: `backend/app/Services/ReadingService.php`, linha 56.
- Problema: `if ($payload['value_m3'] == 0.0)` usa comparacao frouxa. Valores como `0.000` do banco funcionam, mas e fragil.
- Impacto: comportamento inconsistente com arredondamentos.
- Severidade: P2 (medio).
- Como melhorar: usar `<= 0.0` apos validacao min=0, ou definir tolerancia numerica.
- Vale a pena alterar agora: sim, e trivial.

**P1 - Delete de hidrometro nao invalida cache do dashboard**

- Arquivo: `backend/app/Services/HydrometerService.php`, metodo `delete`.
- Problema: apos exclusao, o cache do dashboard continua com contagem antiga ate expirar.
- Impacto: resumo mostra hidrometro que nao existe mais.
- Severidade: P1 (alto).
- Como melhorar: chamar `DashboardService::invalidateCache()` no metodo `delete`.
- Vale a pena alterar agora: sim.

**P2 - Hardcoded threshold no simulador e no ReadingService**

- Arquivo: `backend/app/Console/Commands/SimulateIotCommand.php`, linha 84; `backend/app/Services/ReadingService.php`, linha 23.
- Problema: `10` esta escrito diretamente como limiar de alerta no simulador; constante existe no ReadingService, mas nao e reutilizada.
- Impacto: inconsistencia caso o limiar mude.
- Severidade: P2 (medio).
- Como melhorar: exportar a constante ou configurar via `config('services.ingest.high_consumption_threshold')`.
- Vale a pena alterar agora: sim, e simples.

**P2 - HydrometerService::getDetails nao usa eager loading para readings do grafico**

- Arquivo: `backend/app/Services/HydrometerService.php`, linhas 112-117.
- Problema: atribui `chart_data` via query raw, entao nao ha N+1, mas o HydrometerResource usa `$this->whenLoaded('readings')` e as leituras nunca sao carregadas nesse cenario. No entanto, `readings()` e chamada no Controller para listar paginadamente, e isso e OK.
- Impacto: confusao de responsabilidade; readings nao vem no detalhe apesar do resource prever.
- Severidade: P3 (baixo).
- Como melhorar: remover `readings` do resource de detalhe ou carregar via eager loading quando necessario.
- Vale a pena alterar agora: nao e urgente.

**P2 - HydrometerSeeder usa funcoes nao criptograficas para dados sensiveis? Nao aplicavel, mas gera coordenadas deterministicas?**

- Arquivo: `backend/database/seeders/HydrometerSeeder.php`.
- Problema: senhas de admin/operator geradas via `Str::random(16)` e impressas no terminal. Isso expoe credenciais em logs.
- Impacto: vazamento de senhas administrativas em logs de CI/CD ou terminal compartilhado.
- Severidade: P1 (alto).
- Como melhorar: requerer `ADMIN_SEED_PASSWORD` e `OPERATOR_SEED_PASSWORD` definidos; falhar o seeder se nao estiverem setados, ou gerar e nunca imprimir.
- Vale a pena alterar agora: sim.

**P2 - Leituras historicas no seeder nao usam factory**

- Arquivo: `backend/database/seeders/HydrometerSeeder.php`, linhas 74-98.
- Problema: insercao em massa via `DB::table('readings')->insert()` ignora eventos e castings do model.
- Impacto: baixo; e apenas seed, mas dificulta manutencao.
- Severidade: P3 (baixo).
- Como melhorar: usar factories ou model com chunk, se performance permitir.

**P2 - Rotas publicas `/auth/register` e `/auth/login` sem rate limit por IP**

- Arquivo: `backend/routes/api.php`, linhas 15-18.
- Problema: apesar de `throttle:5,1`, o rate limit usa o padrao do Laravel por user autenticado. Requests nao autenticadas compartilham o mesmo bucket global por IP, mas em APIs publicas isso e insuficiente contra distribuidos.
- Impacto: ataques de forca bruta e enumeracao de email.
- Severidade: P1 (alto).
- Como melhorar: adicionar rate limit customizado por IP + endpoint, captcha opcional, e atraso exponencial.
- Vale a pena alterar agora: sim, e critico para seguranca.

**P3 - Mensagens de validacao duplicadas entre Store e Update**

- Arquivo: `backend/app/Http/Requests/StoreHydrometerRequest.php`, `UpdateHydrometerRequest.php`.
- Problema: `messages()` esta apenas em Store; Update nao customiza mensagens.
- Impacto: baixo, fallback para lang pt_BR.
- Severidade: P3 (baixo).
- Como melhorar: centralizar mensagens compartilhadas.

### 3.2 Frontend

**P0 - Tratamento de erros de rede fraco / sem fallback offline**

- Arquivo: `frontend/src/services/api.ts`, linhas 61-92.
- Problema: o handler de erro so trata `error.response`. Se o backend estiver offline, o erro sera `Network Error` sem status, caindo na mensagem generica.
- Impacto: usuario nao e informado se esta sem internet ou se a API caiu.
- Severidade: P1 (alto).
- Como melhorar: tratar `error.code === 'ERR_NETWORK'` e `error.request` para exibir mensagem especifica.
- Vale a pena alterar agora: sim.

**P1 - Token persistido em localStorage**

- Arquivo: `frontend/src/stores/auth.ts`, linhas 14, 22, 35.
- Problema: `localStorage` e vulneravel a XSS. Embora o CSP restrinja scripts inline, persistir token em localStorage nao e recomendado para aplicacoes sensiveis.
- Impacto: se houver brecha XSS, o token e facilmente exfiltrado.
- Severidade: P1 (alto).
- Como melhorar: usar cookies `HttpOnly` para Sanctum SPA ou, alternativamente, sessionStorage + refresh token rotation.
- Vale a pena alterar agora: depende da estrategia de autenticacao; documentar e planejar.

**P1 - Redirect query param aberto a open redirect parcial**

- Arquivo: `frontend/src/views/LoginView.vue`, linhas 55-57.
- Problema: o codigo verifica `redirect.startsWith('/') && !redirect.startsWith('//')`, mas nao valida o path. Um link `/login?redirect=/\/evildomain.com` ainda pode ser manipulado.
- Impacto: open redirect limitado.
- Severidade: P2 (medio).
- Como melhorar: validar contra uma whitelist de rotas internas ou usar `new URL(redirect, window.location.origin)`.
- Vale a pena alterar agora: sim.

**P1 - Formularios de criacao/ediciao convertem coordenadas via cast `as unknown`**

- Arquivo: `frontend/src/components/CreateHydrometerModal.vue`, linhas 38-42; `EditHydrometerModal.vue`, linhas 59-63.
- Problema: o formulario mantem lat/lng como string e faz cast perigoso `as unknown as Omit<Hydrometer, ...>` para encaixar no tipo. Se a string for vazia, envia `''` ao backend.
- Impacto: request pode falhar silenciosamente ou enviar dados invalidos; perda de type safety.
- Severidade: P1 (alto).
- Como melhorar: criar tipo de payload separado (`CreateHydrometerPayload`) com campos corretos e validacao numerica; nao usar cast.
- Vale a pena alterar agora: sim.

**P2 - Componentes Chart usam `setTimeout` para animacao**

- Arquivo: `frontend/src/components/ConsumptionChart.vue`, linhas 81-101; `StatusDonutChart.vue`, linhas 108-128.
- Problema: dados reais sao aplicados com `setTimeout` para efeito visual, criando estado temporario inconsistente.
- Impacto: testes de snapshot podem falhar; usuario ve grafico vazio por 400-600ms.
- Severidade: P2 (medio).
- Como melhorar: aplicar dados imediatamente e usar animacao do Chart.js; remover setTimeout.
- Vale a pena alterar agora: nao e urgente, mas e uma melhoria de UX.

**P2 - Dashboard realiza polling agressivo e acumula timers**

- Arquivo: `frontend/src/views/DashboardView.vue`, linhas 38-95; `MapPageView.vue`, linhas 29-107.
- Problema: polling a cada 15s/5s com `AbortController`, porem `createAbortController` e chamado em cada `refreshDashboard` e nao ha cancelamento entre telas.
- Impacto: ao navegar rapidamente, requests concorrentes podem competir e toast de erro e exibido.
- Severidade: P2 (medio).
- Como melhorar: usar `watchEffect` com cleanup, ou mover polling para um unico service worker/store.
- Vale a pena alterar agora: recomendado.

**P2 - ToastContainer nao limita numero maximo de toasts**

- Arquivo: `frontend/src/stores/toast.ts`, linhas 38-46.
- Problema: novos toasts sao adicionados indefinidamente.
- Impacto: spam de notificacoes em caso de erros repetidos (ex.: polling offline).
- Severidade: P2 (medio).
- Como melhorar: limitar a 3-5 toasts visiveis e agrupar duplicados.
- Vale a pena alterar agora: sim, baixo esforco.

**P2 - Uso de `style="white-space: pre-line"` inline no Login**

- Arquivo: `frontend/src/views/LoginView.vue`, linha 94.
- Problema: estilo inline misturado com Tailwind.
- Impacto: manutencao e consistencia visual.
- Severidade: P3 (baixo).
- Como melhorar: mover para classe utilitaria.

**P3 - Hardcoded coordenadas de Bocaiuva no MapView**

- Arquivo: `frontend/src/components/MapView.vue`, linha 36.
- Problema: centro do mapa esta hardcoded; se o backend expandir para outras cidades, o mapa nao se ajusta.
- Impacto: baixo para escopo atual.
- Severidade: P3 (baixo).
- Como melhorar: calcular bounds a partir dos hidrometros ou receber centro da API.

**P3 - `recentAlerts` no Dashboard trunca sem ordenacao garantida**

- Arquivo: `frontend/src/stores/dashboard.ts`, linha 77.
- Problema: `data.data || data` e `.slice(0, 5)`. Depende do backend ordenar; API de alertas ordena por `latest`, entao funciona.
- Impacto: baixo.
- Severidade: P3 (baixo).

## 4. Seguranca

### 4.1 Autenticacao e autorizacao

**P1 - Registro publico sem verificacao de email e sem recaptcha**

- Arquivo: `backend/app/Http/Controllers/AuthController.php`, metodo `register`.
- Problema: qualquer pessoa pode criar conta e obter token. Nao ha confirmacao de email, recaptcha, nem aprovacao admin.
- Impacto: criacao massiva de contas, enumeracao de emails, uso indevido.
- Severidade: P1 (alto).
- Como melhorar: desabilitar registro publico em producao ou exigir convite/email verification/captcha.
- Vale a pena alterar agora: sim.

**P1 - Login revoga todos os tokens anteriores**

- Arquivo: `backend/app/Http/Controllers/AuthController.php`, linha 73.
- Problema: ao logar, todos os tokens existentes do usuario sao deletados. Isso invalida sessoes em outros dispositivos.
- Impacto: UX ruim para multi-device; se houver refresh, pode deslogar usuario legitimamente.
- Severidade: P2 (medio).
- Como melhorar: manter tokens ate expiracao ou implementar refresh token rotation explicito.
- Vale a pena alterar agora: mediano; discutir com produto.

**P1 - Middleware `admin` e Policy `resolve` duplicam regra**

- Arquivo: `backend/app/Http/Middleware/EnsureIsAdmin.php`, `backend/app/Policies/AlertPolicy.php`.
- Problema: a mesma checagem `role === 'admin'` esta em dois lugares. Risco de divergencia.
- Impacto: manutencao e possivel bypass se um dos pontos for alterado.
- Severidade: P2 (medio).
- Como melhorar: centralizar em Policy ou Gate e usar `authorize`/`can` consistentemente.
- Vale a pena alterar agora: sim, e refactor simples.

**P2 - CORS com `supports_credentials => false`**

- Arquivo: `backend/config/cors.php`, linha 32.
- Problema: Sanctum SPA geralmente requer credenciais para cookies. Aqui usa tokens via header, entao `false` funciona, mas restringe flexibilidade.
- Impacto: dificulta migracao para cookie-based auth sem mudanca de config.
- Severidade: P3 (baixo).
- Como melhorar: documentar a escolha.

**P2 - Sanctum token_prefix vazio**

- Arquivo: `backend/config/sanctum.php`, linha 68.
- Problema: token_prefix vazio facilita vazamento em logs.
- Impacto: secret scanning nao detecta tokens.
- Severidade: P3 (baixo).
- Como melhorar: definir prefixo como `hydrotrack_`.

### 4.2 Ingestao M2M

**P1 - API key unica e nao rotacionavel**

- Ja detalhado em Arquitetura.

**P2 - Endpoint `/api/ingest` sem validacao de timestamp futuro**

- Arquivo: `backend/app/Http/Requests/IngestReadingRequest.php`, linha 36.
- Problema: `reading_at` so exige ser `date`. Aceita datas futuras e muito antigas.
- Impacto: poluicao de dados e manipulacao de metricas.
- Severidade: P2 (medio).
- Como melhorar: adicionar `before_or_equal:now` e `after_or_equal:now()->subDays(1)`.
- Vale a pena alterar agora: sim.

### 4.3 Headers e CSP

**P2 - CSP permite `unsafe-inline` e `unsafe-eval`**

- Arquivo: `backend/app/Http/Middleware/SecurityHeaders.php`, linha 29.
- Problema: CSP permite scripts inline e eval. Isso enfraquece a protecao contra XSS.
- Impacto: se houver injecao, o atacante pode executar inline scripts.
- Severidade: P2 (medio).
- Como melhorar: remover `unsafe-inline`/`unsafe-eval` e usar nonces/hashes. Em Vue/Vite, hashes sao viaveis.
- Vale a pena alterar agora: nao e urgente, mas e melhoria de seguranca.

**P3 - `X-XSS-Protection` obsoleto**

- Arquivo: `backend/app/Http/Middleware/SecurityHeaders.php`, linha 25.
- Problema: header e considerado obsoleto e pode causar vulnerabilidades de filtragem.
- Impacto: baixo.
- Severidade: P3 (baixo).
- Como melhorar: remover e reforcar CSP.

## 5. Testes

### 5.1 Cobertura atual

**Backend (Pest): 101 testes, 271 assertions, passando.**

- Cobertura boa para: Auth, CRUD Hydrometer, Ingest, Middleware, Services, Watchdog, Dashboard summary, Error handling.
- Testes de Model Hydrometer presentes.
- Alertas: cobre listagem, resolucao, RBAC.

**Frontend (Vitest): 41 testes, passando.**

- Cobertura de: stores (auth, hydrometer, dashboard, alert), api error handler, router guard, composable useIsAdmin, componentes base (StatusBadge, BaseInput, CreateHydrometerModal).

### 5.2 Testes ausentes e prioridade

Priorizados por risco de negocio:

1. **P0 - Teste de deduplicacao de alertas na ingestao**
   - Garantir que ingestar multiplas leituras zeradas para o mesmo hidrometro nao gere alertas duplicados.
   - Risco: spam de alertas e perda de confianca do operador.

2. **P1 - Teste de invalidacao de cache apos delete de hidrometro**
   - Garantir que `DashboardService::invalidateCache` e chamado ao deletar.
   - Risco: inconsistencia de dados no dashboard.

3. **P1 - Teste de carga do endpoint `/api/ingest`**
   - Simular 1000 leituras/minuto.
   - Risco: timeout e perda de leituras em campo.

4. **P1 - Teste E2E do fluxo critico: login -> criar hidrometro -> simular leitura -> alerta -> resolver**
   - Risco: regressao no fluxo de valor do produto.

5. **P1 - Testes de componentes Vue: EditHydrometerModal, DeleteHydrometerDialog, MapView, DashboardView**
   - Risco: regressoes visuais e de interacao.

6. **P1 - Testes de contrato API para todos os endpoints**
   - Garantir estabilidade de JSON responses para o frontend.

7. **P2 - Testes de autenticacao: expiracao de token, logout multi-device, refresh**
   - Risco: seguranca e UX.

8. **P2 - Testes de tratamento de erro de rede no frontend**
   - Simular backend offline e verificar mensagem amigavel.

9. **P2 - Testes de validacao de reading_at futuro/passaado**
   - Risco: integridade dos dados.

10. **P3 - Testes de acessibilidade e responsividade dos componentes Vue**
    - Risco: inclusao e usabilidade mobile.

## 6. CI/CD

**P1 - CI/CD basico existente, mas sem scan de dependencias e deploy com rollback**

- Arquivo: `.github/workflows/ci.yml`, `.github/workflows/deploy.yml`.
- Problema: pipeline roda lint e testes, mas nao executa `composer audit` nem `npm audit`. O deploy para Render nao possui rollback automatico caso o health check falhe.
- Impacto: vulnerabilidades em dependencias podem passar despercebidas; deploy quebrado pode ficar no ar.
- Severidade: P1 (alto).
- Como melhorar: adicionar `composer audit` e `npm audit` ao CI; implementar rollback basico no deploy (manter tag da imagem anterior).
- Vale a pena alterar agora: recomendado, mas nao bloqueante.

## 7. Performance

### 7.1 Pontos positivos

- Cache em summary, consumption e mapa.
- Queries agregadas de status no DashboardService.
- Bulk update/insert no WatchdogCommand.
- CSV export usa chunk de 500 registros.
- Seeder usa insercao em lote.

### 7.2 Problemas

**P2 - `/api/dashboard/map` retorna todos os hidrometros sem paginacao**

- Arquivo: `backend/app/Services/DashboardService.php`, linhas 108-115.
- Problema: a cada 5 segundos o frontend pode carregar milhares de hidrometros.
- Impacto: latencia e uso de banda crescem linearmente com a quantidade de dispositivos.
- Severidade: P2 (medio).
- Como melhorar: paginar mapa ou usar clustering; cache local com atualizacao delta.
- Vale a pena alterar agora: depende do crescimento; planejar para 1000+ devices.

**P2 - Leituras sem particionamento/retencao**

- Arquivo: `backend/database/migrations/2026_05_04_194045_create_readings_table.php`.
- Problema: tabela `readings` crescera indefinidamente. Sem indexacao por `value_m3` (nao necessaria) e sem arquivamento.
- Impacto: queries de dashboard e export ficarao lentas ao longo dos meses.
- Severidade: P2 (medio).
- Como melhorar: policy de retencao (ex.: arquivar leituras antigas), particionamento por mes, ou agregacao diaria.
- Vale a pena alterar agora: nao e urgente, mas deve estar no roadmap.

**P2 - WatchdogCommand carrega todos os hidrometros nao-offline em memoria**

- Arquivo: `backend/app/Console/Commands/WatchdogCommand.php`, linhas 55-60.
- Problema: `->get()` em todos os hidrometros pode ser pesado.
- Impacto: uso de memoria em escala.
- Severidade: P2 (medio).
- Como melhorar: usar `chunk` ou query direta para update/insert sem materializar collection.
- Vale a pena alterar agora: recomendado.

**P3 - Simulador IoT escolhe hidrometro aleatorio e dorme a cada iteracao**

- Arquivo: `backend/app/Console/Commands/SimulateIotCommand.php`.
- Problema: nao e realmente um problema; apenas ineficiente para simular carga massiva.
- Impacto: nao aplicavel em producao.
- Severidade: P3 (baixo).

## 8. Observabilidade

**P0 - Health check `/up` e apenas do Laravel, nao valida banco**

- Arquivo: `backend/bootstrap/app.php`, linha 22; `docker-compose.yml`, linha 21.
- Problema: healthcheck do Docker usa `/up`, que nao verifica conexao com MySQL.
- Impacto: container pode ficar `healthy` mesmo sem banco, causando falhas silenciosas.
- Severidade: P1 (alto).
- Como melhorar: criar endpoint custom `/health` que verifica DB, cache e fila.
- Vale a pena alterar agora: sim.

**P1 - Logs nao estruturados para producao**

- Arquivo: `backend/config/logging.php`.
- Problema: formato padrao `single` e texto. Dificil integrar com Loki/Datadog.
- Impacto: dificuldade para rastrear incidentes.
- Severidade: P2 (medio).
- Como melhorar: adicionar canal `stderr` JSON em producao.
- Vale a pena alterar agora: recomendado.

**P1 - Sem metricas de aplicacao (APM)**

- Problema: nao ha tracking de tempo de ingestao, taxa de alertas, latencia de endpoints.
- Impacto: dificuldade para detectar degradacao.
- Severidade: P2 (medio).
- Como melhorar: expor metricas Prometheus ou integrar APM (Sentry/New Relic).
- Vale a pena alterar agora: nao e urgente, mas importante.

**P2 - Erros de API nao sao correlacionados com request_id**

- Problema: nao ha identificador de rastreamento em respostas.
- Impacto: dificuldade de debug.
- Severidade: P2 (medio).
- Como melhorar: adicionar middleware que injeta `X-Request-Id`.

## 9. Documentacao

**P2 - README afirma que projeto esta "Concluido" e "pronto para producao"**

- Arquivo: `README.md`, linhas 264-275.
- Problema: linguagem gera falsa sensacao de que nao ha divida tecnica. O README e de otima qualidade, mas essa secao e otimista demais.
- Impacto: stakeholders podem subestimar riscos.
- Severidade: P2 (medio).
- Como melhorar: substituir por "MVP entregue" e listar proximos passos.
- Vale a pena alterar agora: sim.

**P2 - Falta documentacao de decisoes de seguranca**

- Problema: nao ha ADR explicando por que localStorage, API key unica, registro publico, etc.
- Impacto: futuros devs podem repetir escolhas sem contexto.
- Severidade: P2 (medio).
- Como melhorar: criar `docs/adr/` ou secao no README.

**P3 - Documentacao Scribe depende de rodar localmente**

- Arquivo: `backend/routes/api.php`.
- Problema: nao ha colecao Postman/Insomnia ou OpenAPI exportado no repo.
- Impacto: onboarding de novos devs.
- Severidade: P3 (baixo).

## 10. Plano de Acao Priorizado

### P0 (critico) - resolver primeiro

1. Implementar deduplicacao de alertas na ingestao para nao gerar multiplos alertas identicos para o mesmo hidrometro.
2. Criar teste que garante a deduplicacao de alertas.
3. Invalidar cache do dashboard ao excluir hidrometro.
4. Criar teste para invalidacao de cache no delete.
5. Criar endpoint `/health` real e atualizar healthcheck do Docker.
6. Melhorar tratamento de erro de rede no frontend.

### P1 (alto) - resolver em seguida

5. Invalidar cache do dashboard ao excluir hidrometro.
6. Remover senhas administrativas do output do seeder ou exigir variaveis de ambiente.
7. Desabilitar registro publico ou adicionar verificacao de email/captcha.
8. Melhorar tratamento de erro de rede no frontend.
9. Remover casts perigosos nos modais de hidrometro e usar tipos de payload adequados.
10. Tighten rate limiting de autenticacao por IP.
11. Implementar teste E2E do fluxo critico.
12. Criar teste de invalidacao de cache no delete.

### P2 (medio) - backlog tecnico

13. Refatorar invalidacao de cache para eventos/listeners.
14. Adicionar validacao de timestamp `reading_at` no backend.
15. Extrair constantes de threshold de consumo para config.
16. Refatorar polling do dashboard para evitar timers concorrentes.
17. Limitar numero de toasts simultaneos e agrupar duplicados.
18. Adicionar logs estruturados JSON em producao.
19. Melhorar CSP removendo unsafe-inline/eval.
20. Implementar metricas basicas de APM.
21. Otimizar WatchdogCommand para nao materializar todos os hidrometros.
22. Adicionar testes para componentes Vue faltantes.
23. Planejar retencao/particionamento da tabela `readings`.
24. Revisar README afirmacoes de "pronto para producao".

### P3 (baixo) - melhorias e polimento

25. Centralizar mensagens de validacao de hidrometro.
26. Remover `X-XSS-Protection` obsoleto.
27. Definir `token_prefix` do Sanctum.
28. Remover setTimeout dos graficos.
29. Documentar ADRs de seguranca.
30. Exportar colecao OpenAPI/Postman no repo.

## 11. Lista de Testes a Implementar (por risco de negocio)

1. **Deduplicacao de alertas na ingestao** (P0)
   - Dado hidrometro com alerta de leitura zerada nao resolvido, ao ingerir nova leitura zerada, nao deve criar segundo alerta.

2. **Deduplicacao de alertas de alto consumo na ingestao** (P0)
   - Dado hidrometro com alerta de alto consumo nao resolvido, ao ingerir nova leitura acima do limiar, nao deve criar segundo alerta.

3. **Cache invalidado ao deletar hidrometro** (P1)
   - Deletar hidrometro deve chamar `Cache::forget('dashboard:summary')` e demais chaves.

4. **Fluxo E2E critico** (P1)
   - Login admin -> criar hidrometro -> simular leitura -> ver alerta -> resolver -> ver dashboard atualizado.

5. **Carga do endpoint /api/ingest** (P1)
   - 1000 requisicoes/minuto com API key valida devem ser processadas sem timeout HTTP.

6. **Tratamento de erro de rede no frontend** (P1)
   - Simular backend offline; toast deve exibir "Servidor indisponivel" ou similar.

7. **Validacao de reading_at** (P2)
   - Datas futuras e muito antigas devem ser rejeitadas com 422.

7. **Watchdog com grande volume de hidrometros** (P2)
   - Criar 10.000 hidrometros offline; comando deve rodar sem estouro de memoria.

8. **Contrato JSON de todos os endpoints** (P2)
   - Cada endpoint deve retornar estrutura esperada; testes de snapshot.

9. **Componentes EditHydrometerModal e DeleteHydrometerDialog** (P2)
   - Renderizacao, eventos e integracao com store.

10. **MapView com interacoes** (P2)
    - Testar renderizacao de marcadores e emissao de evento de clique.

11. **Multi-device login e logout** (P2)
    - Login em dois dispositivos; logout em um nao deve deslogar o outro (se essa for a regra de negocio).

12. **Rate limit de autenticacao por IP** (P2)
    - Apos 5 tentativas falhas, proximas devem ser bloqueadas por 429.

## 12. Conclusao

O HydroTrack e um projeto solido para o nivel de portfolio e demonstra dominio de Laravel e Vue.js. Para atingir o padrao pleno em um ambiente real, o maior trabalho esta em seguranca (especialmente autenticacao e ingestao), CI/CD, observabilidade e testes de carga/E2E. A arquitetura basica esta correta e nao precisa de refactor radical como DDD ou Clean Architecture. O foco deve ser endurecimento incremental, comecando pelos itens P0 e P1 listados.

**Principais achados:**

- 101 testes backend e 41 frontend passam.
- Sem pipeline CI/CD versionada.
- Deduplicacao de alertas ausente e impacta usabilidade.
- Tratamento de erro de rede fraco no frontend.
- Seeder expoe senhas admin em logs.
- Cache nao e invalidado no delete de hidrometro.
- API key de ingestao unica e nao escalavel.

**Proximo passo recomendado:** implementar CI/CD e a deduplicacao de alertas, pois sao de baixo esforco e alto impacto.
