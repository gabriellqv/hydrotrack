<script setup lang="ts">
import { computed, ref, onUnmounted, onActivated, onDeactivated } from 'vue'
import { RouterLink } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'
import { useAuthStore } from '@/stores/auth'
import BaseCard from '@/components/ui/BaseCard.vue'
import DashboardSkeleton from '@/components/ui/DashboardSkeleton.vue'
import ConsumptionChart from '@/components/ConsumptionChart.vue'
import MapView from '@/components/MapView.vue'
import StatusDonutChart from '@/components/StatusDonutChart.vue'
import RecentAlerts from '@/components/RecentAlerts.vue'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import {
  Droplets,
  Wifi,
  WifiOff,
  AlertTriangle,
  Activity,
  Bell,
  TrendingUp,
  MapPin,
  PieChart,
  ArrowRight,
  ShieldAlert,
  RefreshCw,
  Plus,
  Download,
  Gauge,
} from 'lucide-vue-next'

const store = useDashboardStore()
const authStore = useAuthStore()

const periodOptions: { days: 7 | 30 | 90; label: string }[] = [
  { days: 7, label: '7d' },
  { days: 30, label: '30d' },
  { days: 90, label: '90d' },
]

const chartTitle = computed(() => {
  const labels: Record<number, string> = {
    7: 'Consumo Diário (últimos 7 dias)',
    30: 'Consumo Diário (últimos 30 dias)',
    90: 'Consumo Diário (últimos 90 dias)',
  }
  return labels[store.selectedDays] || 'Consumo Diário'
})

async function changePeriod(days: 7 | 30 | 90) {
  await store.fetchConsumption(days)
}

const POLLING_INTERVAL = 15_000
let pollingTimer: ReturnType<typeof setInterval> | null = null

async function refreshDashboard() {
  try {
    await Promise.all([
      store.fetchSummary(),
      store.fetchConsumption(),
      store.fetchMap(),
      store.fetchAlerts(),
    ])
  } catch {
    // Falhas de rede já são tratadas pelo interceptor da API; aqui evitamos
    // que o polling em background gere unhandled rejection.
  }
}

/**
 * Inicia o polling. Como a view é mantida viva pelo KeepAlive do App,
 * o ciclo é controlado por onActivated/onDeactivated (e não por onMounted).
 */
function startPolling() {
  stopPolling()
  refreshDashboard()
  pollingTimer = setInterval(refreshDashboard, POLLING_INTERVAL)
}

function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

onActivated(() => {
  startPolling()
})

onDeactivated(() => {
  stopPolling()
})

onUnmounted(() => {
  stopPolling()
})

// Estado do botão de sincronização manual
const isRefreshing = ref(false)
const lastSyncTime = ref<string>('agora')

async function handleManualSync() {
  if (isRefreshing.value) return
  isRefreshing.value = true
  try {
    await refreshDashboard()
    lastSyncTime.value = new Date().toLocaleTimeString('pt-BR', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    })
  } finally {
    setTimeout(() => {
      isRefreshing.value = false
    }, 600)
  }
}

// Exportação rápida de dados em CSV
function exportDashboardCSV() {
  if (!store.consumption.length) return
  const headers = 'Data,Consumo_m3\n'
  const rows = store.consumption.map((p) => `${p.date},${p.total_m3}`).join('\n')
  const blob = new Blob([headers + rows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `hydrotrack_consumo_${store.selectedDays}d.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Cálculos de porcentagem dos KPIs
const totalCount = computed(() => store.summary?.total_hydrometers || 0)
const onlinePct = computed(() =>
  totalCount.value > 0 ? (((store.summary?.online || 0) / totalCount.value) * 100).toFixed(0) : '0',
)
const offlinePct = computed(() =>
  totalCount.value > 0
    ? (((store.summary?.offline || 0) / totalCount.value) * 100).toFixed(0)
    : '0',
)
const alertPct = computed(() =>
  totalCount.value > 0 ? (((store.summary?.alert || 0) / totalCount.value) * 100).toFixed(0) : '0',
)

// Indicadores analíticos do gráfico de consumo
const periodTotalM3 = computed(() => {
  return store.consumption.reduce((acc, p) => {
    const val =
      typeof p.total_m3 === 'number' ? p.total_m3 : parseFloat(p.total_m3 as unknown as string)
    return acc + (isNaN(val) ? 0 : val)
  }, 0)
})

const periodDailyAverage = computed(() => {
  if (!store.consumption.length) return 0
  return periodTotalM3.value / store.consumption.length
})

const periodPeak = computed(() => {
  if (!store.consumption.length) return null
  let max = -Infinity
  let peakPoint: (typeof store.consumption)[0] | null = null
  for (const p of store.consumption) {
    const val =
      typeof p.total_m3 === 'number' ? p.total_m3 : parseFloat(p.total_m3 as unknown as string)
    if (!isNaN(val) && val > max) {
      max = val
      peakPoint = p
    }
  }
  if (!peakPoint) return null
  const date = new Date(peakPoint.date)
  const formattedDate = date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
  return { val: max, formattedDate }
})

// Índice de Saúde Operacional da Malha (Health Score)
const healthScore = computed(() => {
  if (!totalCount.value) return 100
  const onlineCount = store.summary?.online || 0
  return Math.round((onlineCount / totalCount.value) * 100)
})

const healthStatus = computed(() => {
  if (healthScore.value >= 80) {
    return {
      label: 'Excelente',
      color: 'text-emerald-400',
      badgeBg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
      barColor: 'from-emerald-500 to-teal-400',
    }
  }
  if (healthScore.value >= 50) {
    return {
      label: 'Operacional',
      color: 'text-sky-400',
      badgeBg: 'bg-sky-500/10 text-sky-400 border-sky-500/20',
      barColor: 'from-sky-500 to-blue-500',
    }
  }
  return {
    label: 'Atenção Crítica',
    color: 'text-rose-400',
    badgeBg: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    barColor: 'from-amber-500 to-rose-500',
  }
})

const kpiCards = computed(() => [
  {
    key: 'total',
    label: 'Total Ativos',
    sub: 'Hidrômetros na malha',
    value: store.summary?.total_hydrometers ?? '—',
    icon: Droplets,
    iconBg: 'bg-primary-500/15 text-primary-400',
    borderHover: 'hover:border-primary-500/40',
  },
  {
    key: 'online',
    label: 'Online',
    sub: `${onlinePct.value}% da infraestrutura`,
    value: store.summary?.online ?? '—',
    icon: Wifi,
    iconBg: 'bg-emerald-500/15 text-emerald-400',
    borderHover: 'hover:border-emerald-500/40',
  },
  {
    key: 'offline',
    label: 'Offline',
    sub: `${offlinePct.value}% sem transmissão`,
    value: store.summary?.offline ?? '—',
    icon: WifiOff,
    iconBg: 'bg-slate-500/15 text-slate-400',
    borderHover: 'hover:border-slate-500/40',
  },
  {
    key: 'alert',
    label: 'Em Alerta',
    sub: `${alertPct.value}% anomalia detectada`,
    value: store.summary?.alert ?? '—',
    icon: AlertTriangle,
    iconBg: 'bg-rose-500/15 text-rose-400',
    borderHover: 'hover:border-rose-500/40',
  },
  {
    key: 'readings',
    label: 'Leituras Hoje',
    sub: 'Pacotes IoT processados',
    value: store.summary?.total_readings_today ?? '—',
    icon: Activity,
    iconBg: 'bg-sky-500/15 text-sky-400',
    borderHover: 'hover:border-sky-500/40',
  },
  {
    key: 'pending_alerts',
    label: 'Alertas Pendentes',
    sub: 'Aguardando resolução',
    value: store.summary?.pending_alerts ?? '—',
    icon: Bell,
    iconBg: 'bg-amber-500/15 text-amber-400',
    borderHover: 'hover:border-amber-500/40',
  },
])
</script>

<template>
  <DashboardSkeleton v-if="!store.summary" />
  <div v-else class="animate-fade-in space-y-6 lg:space-y-8 pb-10">
    <!-- Header do Dashboard + Barra de Ações Rápidas -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-3">
          <h1 class="text-2xl lg:text-3xl font-bold text-text-heading tracking-tight">Dashboard</h1>
          <!-- Status da Rede IoT Conectada (Design translúcido e harmonizado) -->
          <div
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 backdrop-blur-sm text-xs font-medium transition-all"
          >
            <span class="relative flex h-2 w-2">
              <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
              ></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-emerald-700 dark:text-emerald-300/90 font-medium"
              >Rede IoT Conectada</span
            >
            <span class="text-emerald-500/40">•</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-bold">Bocaiúva-MG</span>
          </div>
        </div>
        <p class="text-sm text-text-muted mt-0.5">
          Visão geral e telemetria hídrica inteligente em tempo real
        </p>
      </div>

      <!-- Barra de Ações Rápidas (Quick Actions) -->
      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Botão Sincronizar Agora -->
        <button
          @click="handleManualSync"
          :disabled="isRefreshing"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-surface-card hover:bg-surface-hover border border-border/80 text-text-heading transition-all duration-200 cursor-pointer shadow-sm disabled:opacity-60 active:scale-[0.98]"
          title="Atualizar dados agora"
        >
          <RefreshCw
            :class="[
              'h-3.5 w-3.5 text-text-muted',
              { 'animate-spin text-primary-400': isRefreshing },
            ]"
          />
          <span>Sincronizar</span>
          <span class="text-[10px] text-text-muted hidden sm:inline">({{ lastSyncTime }})</span>
        </button>

        <!-- Botão Exportar CSV -->
        <button
          @click="exportDashboardCSV"
          class="inline-flex items-center gap-2 px-4.5 py-2 rounded-full text-xs font-semibold bg-surface-card hover:bg-surface-hover border border-border/80 text-text-heading transition-all duration-200 cursor-pointer shadow-sm active:scale-[0.98]"
          title="Exportar dados de consumo em CSV"
        >
          <Download class="h-3.5 w-3.5 text-text-muted" />
          <span>Exportar</span>
        </button>

        <!-- Botão Novo Hidrômetro (Apenas Administrador) -->
        <RouterLink
          v-if="authStore.user?.role === 'admin'"
          to="/hydrometers"
          class="inline-flex items-center gap-2 px-4.5 py-2 rounded-full text-xs font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-md shadow-primary-900/25 border border-transparent transition-all duration-200 cursor-pointer active:scale-[0.98]"
        >
          <Plus class="h-4 w-4 stroke-[2.5]" />
          <span>Novo Hidrômetro</span>
        </RouterLink>
      </div>
    </div>

    <!-- Barra de Saúde Operacional da Malha (Network Health Score) -->
    <div
      class="p-4 rounded-2xl bg-surface-card/60 backdrop-blur-xl border border-border/60 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div class="flex items-center gap-3">
        <div class="p-2.5 rounded-xl bg-primary-500/10 text-primary-400 shrink-0">
          <Gauge class="h-5 w-5" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">
              Saúde Operacional da Malha
            </span>
            <span
              :class="[
                'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                healthStatus.badgeBg,
              ]"
            >
              {{ healthStatus.label }}
            </span>
          </div>
          <p class="text-xs text-text-muted mt-0.5">
            {{ store.summary?.online ?? 0 }} de {{ totalCount }} sensores transmitindo telemetria
            regularmente
          </p>
        </div>
      </div>

      <!-- Barra de Progresso do SLA -->
      <div class="flex items-center gap-3 w-full md:w-72 shrink-0">
        <div class="flex-1 bg-surface-hover/80 h-2.5 rounded-full overflow-hidden p-0.5">
          <div
            class="h-full rounded-full bg-gradient-to-r transition-all duration-1000 ease-out"
            :class="healthStatus.barColor"
            :style="{ width: `${healthScore}%` }"
          ></div>
        </div>
        <span class="text-sm font-extrabold text-text-heading tabular-nums min-w-[3rem] text-right">
          <AnimatedCounter :value="healthScore" :formatter="(v) => Math.round(v) + '%'" />
        </span>
      </div>
    </div>

    <!-- Linha Superior de KPIs: 6 Cards Proporcionais -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 lg:gap-4">
      <BaseCard
        v-for="kpi in kpiCards"
        :key="kpi.key"
        compact
        class="flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border-border/50"
        :class="kpi.borderHover"
      >
        <div class="flex items-center justify-between gap-2 mb-2">
          <span class="text-xs font-bold text-text-muted uppercase tracking-wider truncate">
            {{ kpi.label }}
          </span>
          <div :class="['p-1.5 rounded-lg shrink-0', kpi.iconBg]">
            <component :is="kpi.icon" class="h-3.5 w-3.5" />
          </div>
        </div>

        <div class="my-1">
          <div class="text-2xl lg:text-3xl font-extrabold text-text-heading tracking-tight">
            <AnimatedCounter :value="kpi.value" />
          </div>
        </div>

        <div class="mt-2 pt-2 border-t border-border/30 flex items-center justify-between">
          <span class="text-[11px] text-text-muted truncate">{{ kpi.sub }}</span>
        </div>
      </BaseCard>
    </div>

    <!-- Grid Principal (Bloco Médio): Consumo Diário + Mapa -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">
      <!-- Gráfico de Consumo -->
      <BaseCard
        class="xl:col-span-7 flex flex-col p-5 lg:p-6 transition-all duration-300 hover:border-primary-500/30"
      >
        <div
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 shrink-0"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-primary-500/10 text-primary-400 shrink-0">
              <TrendingUp class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-bold text-text-heading tracking-tight">
                {{ chartTitle }}
              </h2>
              <p class="text-xs text-text-muted">Vazão agregada medida em metros cúbicos (m³)</p>
            </div>
          </div>

          <!-- Seletor de Período -->
          <div
            class="flex gap-1 bg-surface/60 p-1 rounded-xl border border-border/60 self-start sm:self-auto shadow-inner"
          >
            <button
              v-for="opt in periodOptions"
              :key="opt.days"
              @click="changePeriod(opt.days)"
              :class="[
                'px-3 py-1 rounded-lg text-xs font-bold transition-all duration-200 cursor-pointer',
                store.selectedDays === opt.days
                  ? 'bg-primary-600 text-white shadow-md shadow-primary-900/30 scale-105'
                  : 'text-text-muted hover:text-text-heading hover:bg-surface-hover/50',
              ]"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Mini-Barra de Inteligência Analítica do Período -->
        <div
          class="grid grid-cols-3 gap-2 sm:gap-3 mb-4 p-3 rounded-xl bg-surface/40 border border-border/40 text-xs shrink-0"
        >
          <div>
            <span class="text-[11px] text-text-muted font-medium block">Volume do Período</span>
            <span class="text-sm sm:text-base font-bold text-text-heading">
              <AnimatedCounter
                :value="periodTotalM3"
                :formatter="
                  (v) =>
                    v.toLocaleString('pt-BR', {
                      minimumFractionDigits: 1,
                      maximumFractionDigits: 1,
                    }) + ' m³'
                "
              />
            </span>
          </div>

          <div class="border-l border-border/40 pl-2 sm:pl-3">
            <span class="text-[11px] text-text-muted font-medium block">Média Diária</span>
            <span class="text-sm sm:text-base font-bold text-text-heading">
              <AnimatedCounter
                :value="periodDailyAverage"
                :formatter="
                  (v) =>
                    v.toLocaleString('pt-BR', {
                      minimumFractionDigits: 1,
                      maximumFractionDigits: 1,
                    }) + ' m³'
                "
              />
            </span>
          </div>

          <div class="border-l border-border/40 pl-2 sm:pl-3">
            <span class="text-[11px] text-text-muted font-medium block">Pico Máximo</span>
            <span
              class="text-sm sm:text-base font-bold text-text-heading truncate flex items-baseline gap-1"
            >
              <template v-if="periodPeak">
                <AnimatedCounter
                  :value="periodPeak.val"
                  :formatter="
                    (v) =>
                      v.toLocaleString('pt-BR', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                      }) + ' m³'
                  "
                />
                <span class="text-[10px] text-text-muted font-normal"
                  >({{ periodPeak.formattedDate }})</span
                >
              </template>
              <span v-else>—</span>
            </span>
          </div>
        </div>

        <!-- Área do Gráfico Reativo com Altura Proporcional -->
        <div class="h-[290px] sm:h-[320px] lg:h-[340px] w-full min-h-0 pt-1">
          <ConsumptionChart v-if="store.consumption.length" :data="store.consumption" />
          <div v-else class="h-full flex items-center justify-center">
            <p class="text-sm text-text-muted">Carregando dados de consumo...</p>
          </div>
        </div>
      </BaseCard>

      <!-- Mapa de Hidrômetros -->
      <BaseCard
        class="xl:col-span-5 flex flex-col p-5 lg:p-6 transition-all duration-300 hover:border-emerald-500/30"
      >
        <div class="flex items-center justify-between gap-3 mb-4 shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 shrink-0">
              <MapPin class="h-5 w-5" />
            </div>
            <div class="min-w-0">
              <h2 class="text-base font-bold text-text-heading tracking-tight truncate">
                Monitoramento Geoespacial
              </h2>
              <p class="text-xs text-text-muted truncate">Geolocalização de hidrômetros em campo</p>
            </div>
          </div>

          <RouterLink
            to="/map"
            class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:bg-primary-500/10 transition-all"
          >
            <span class="whitespace-nowrap">Ver mapa</span>
            <ArrowRight class="h-3.5 w-3.5 shrink-0" />
          </RouterLink>
        </div>

        <!-- Área do Mapa com Altura Proporcional -->
        <div
          class="h-[365px] sm:h-[395px] lg:h-[415px] w-full rounded-xl overflow-hidden border border-border/40 relative shadow-inner"
        >
          <MapView :hydrometers="store.mapHydrometers" class="w-full h-full" />
        </div>
      </BaseCard>
    </div>

    <!-- Grid Secundário (Bloco Inferior): Distribuição por Status + Alertas -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">
      <!-- Distribuição por Status -->
      <BaseCard
        class="xl:col-span-5 flex flex-col p-5 lg:p-6 transition-all duration-300 hover:border-indigo-500/30"
      >
        <div class="flex items-center justify-between mb-3 shrink-0">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 shrink-0">
              <PieChart class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-bold text-text-heading tracking-tight">
                Distribuição por Status
              </h2>
              <p class="text-xs text-text-muted">Saúde operacional e conectividade</p>
            </div>
          </div>
        </div>

        <div class="h-auto sm:h-[270px] lg:h-[290px] w-full flex items-center justify-center">
          <StatusDonutChart v-if="store.summary" :summary="store.summary" class="w-full h-full" />
          <p v-else class="text-sm text-text-muted text-center py-10">Carregando indicadores...</p>
        </div>
      </BaseCard>

      <!-- Central de Alertas Recentes -->
      <BaseCard
        class="xl:col-span-7 flex flex-col p-5 lg:p-6 transition-all duration-300 hover:border-rose-500/30"
      >
        <div class="flex items-center justify-between gap-3 mb-3 shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400 shrink-0">
              <ShieldAlert class="h-5 w-5" />
            </div>
            <div class="min-w-0">
              <h2 class="text-base font-bold text-text-heading tracking-tight truncate">
                Últimas Anomalias
              </h2>
              <p class="text-xs text-text-muted truncate">
                Eventos recentes de consumo atípico e offline
              </p>
            </div>
          </div>

          <RouterLink
            to="/alerts"
            class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:bg-primary-500/10 transition-all"
          >
            <span class="whitespace-nowrap sm:hidden">Ver alertas</span>
            <span class="whitespace-nowrap hidden sm:inline">Central de alertas</span>
            <ArrowRight class="h-3.5 w-3.5 shrink-0" />
          </RouterLink>
        </div>

        <div class="h-[270px] lg:h-[290px] w-full min-h-0 overflow-y-auto pr-1">
          <RecentAlerts :alerts="store.recentAlerts" />
        </div>
      </BaseCard>
    </div>
  </div>
</template>
