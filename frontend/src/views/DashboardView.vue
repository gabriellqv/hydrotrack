<script setup lang="ts">
import { computed } from 'vue'
import { onMounted, onUnmounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import ConsumptionChart from '@/components/ConsumptionChart.vue'
import MapView from '@/components/MapView.vue'
import StatusDonutChart from '@/components/StatusDonutChart.vue'
import RecentAlerts from '@/components/RecentAlerts.vue'
import { Droplets, Wifi, WifiOff, AlertTriangle, BarChart3, Bell } from 'lucide-vue-next'

/**
 * View Principal do Dashboard.
 *
 * Consolida as métricas do sistema em 4 blocos: cards de resumo,
 * gráfico de consumo + mapa interativo, e donut de status + alertas recentes.
 *
 * Implementa polling a cada 5 segundos para atualização em tempo real.
 */
import { useTheme } from '@/composables/useTheme'

const store = useDashboardStore()
const { isDark } = useTheme()

/** Labels para os botões do seletor de período */
const periodOptions: { days: 7 | 30 | 90; label: string }[] = [
  { days: 7, label: '7d' },
  { days: 30, label: '30d' },
  { days: 90, label: '90d' },
]

/** Título dinâmico baseado no período selecionado */
const chartTitle = computed(() => {
  const labels: Record<number, string> = {
    7: 'Consumo Diário (últimos 7 dias)',
    30: 'Consumo Diário (últimos 30 dias)',
    90: 'Consumo Diário (últimos 90 dias)',
  }
  return labels[store.selectedDays] || 'Consumo Diário'
})

/** Altera o período e recarrega os dados de consumo */
async function changePeriod(days: 7 | 30 | 90) {
  await store.fetchConsumption(days)
}

/** Intervalo de polling em milissegundos */
const POLLING_INTERVAL = 5_000

let pollingTimer: ReturnType<typeof setInterval> | null = null

/** Busca todos os dados do dashboard */
async function refreshDashboard() {
  await Promise.all([
    store.fetchSummary(),
    store.fetchConsumption(),
    store.fetchMap(),
    store.fetchAlerts(),
  ])
}

onMounted(async () => {
  await refreshDashboard()

  pollingTimer = setInterval(refreshDashboard, POLLING_INTERVAL)
})

onUnmounted(() => {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
})

const summaryCards = [
  {
    key: 'total_hydrometers',
    label: 'Total',
    icon: Droplets,
    color: 'text-primary-400',
    bg: 'bg-primary-600/20',
  },
  { key: 'online', label: 'Online', icon: Wifi, color: 'text-green-400', bg: 'bg-green-600/20' },
  {
    key: 'offline',
    label: 'Offline',
    icon: WifiOff,
    color: 'text-slate-400',
    bg: 'bg-slate-600/20',
  },
  {
    key: 'alert',
    label: 'Em Alerta',
    icon: AlertTriangle,
    color: 'text-red-400',
    bg: 'bg-red-600/20',
  },
  {
    key: 'total_readings_today',
    label: 'Leituras Hoje',
    icon: BarChart3,
    color: 'text-blue-400',
    bg: 'bg-blue-600/20',
  },
  {
    key: 'pending_alerts',
    label: 'Alertas Pendentes',
    icon: Bell,
    color: 'text-amber-400',
    bg: 'bg-amber-600/20',
  },
]
</script>

<template>
  <div
    class="animate-fade-in flex flex-col min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] space-y-3 lg:min-h-0"
  >
    <div class="shrink-0">
      <h1 class="text-2xl font-bold text-text-heading leading-tight">Dashboard</h1>
      <p class="text-sm text-text-muted">Visão geral do sistema de monitoramento</p>
    </div>

    <!-- Wrapper relativo para restringir o vazamento de luz apenas à área do Card -->
    <div class="relative flex-1 flex flex-col z-0 lg:min-h-0">
      <!-- Background Decorativo super intenso para o Glassmorphism brilhar (Apenas Dark Mode) -->
      <div
        class="absolute inset-0 overflow-hidden pointer-events-none -z-10 rounded-xl glass-blobs"
        :class="{ hidden: !isDark }"
      >
        <div
          class="absolute top-[-50px] left-[-50px] w-[500px] h-[500px] rounded-full bg-primary-500/40 blur-[100px]"
        ></div>
        <div
          class="absolute bottom-[-50px] right-[-50px] w-[400px] h-[400px] rounded-full bg-blue-500/30 blur-[80px]"
        ></div>
      </div>

      <!-- Unified Dashboard Wrapper -->
      <BaseCard class="flex-1 flex flex-col !p-4 lg:!p-5 lg:min-h-0">
        <!-- Layout 2x2: Gráfico | Mapa // Status | Alertas -->
        <div
          class="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-3 gap-6 lg:gap-8 flex-1 lg:min-h-0"
        >
          <!-- Top Left: Gráfico -->
          <div class="flex flex-col lg:row-span-2 min-h-[300px] lg:min-h-0">
            <div class="flex items-center justify-between mb-3 shrink-0">
              <h3 class="text-sm font-bold text-text-heading uppercase tracking-wider">
                {{ chartTitle }}
              </h3>
              <div class="flex gap-1">
                <button
                  v-for="opt in periodOptions"
                  :key="opt.days"
                  @click="changePeriod(opt.days)"
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200',
                    store.selectedDays === opt.days
                      ? 'bg-primary-600 text-white shadow-lg shadow-primary-900/20'
                      : 'bg-surface-card border border-border text-text-muted hover:text-text-heading hover:border-primary-500/30',
                  ]"
                >
                  {{ opt.label }}
                </button>
              </div>
            </div>
            <div class="flex-1 w-full min-h-0">
              <ConsumptionChart v-if="store.consumption.length" :data="store.consumption" />
              <p v-else class="text-sm text-text-muted text-center py-8">Carregando dados...</p>
            </div>
          </div>

          <!-- Top Right: Mapa -->
          <div class="flex flex-col lg:row-span-2 min-h-0">
            <h3 class="text-sm font-bold text-text-heading mb-3 uppercase tracking-wider shrink-0">
              Mapa de Hidrômetros
            </h3>
            <!-- Data Ribbon HUD -->
            <div
              class="flex flex-wrap sm:flex-nowrap w-full bg-surface/40 rounded-lg border border-border/50 py-2.5 mb-3 shrink-0"
            >
              <div
                v-for="(card, index) in summaryCards"
                :key="card.key"
                class="flex flex-1 flex-col items-center justify-center relative min-w-[50%] sm:min-w-0 py-1 sm:py-0"
                :class="{ 'sm:border-l sm:border-border/50': index > 0 }"
              >
                <div class="flex items-center gap-1.5 mb-1 w-full justify-center px-1">
                  <component :is="card.icon" :class="['h-3.5 w-3.5 shrink-0', card.color]" />
                  <span class="text-[10px] font-medium text-text-muted truncate">{{
                    card.label
                  }}</span>
                </div>
                <span class="text-base font-bold text-text-heading leading-none">
                  {{ store.summary?.[card.key as keyof typeof store.summary] ?? '—' }}
                </span>
              </div>
            </div>

            <div
              class="flex-1 w-full min-h-[500px] lg:min-h-0 relative rounded-lg overflow-hidden border border-border/30 flex"
            >
              <MapView :hydrometers="store.mapHydrometers" class="flex-1" />
            </div>
          </div>

          <!-- Bottom Left: Donut Chart -->
          <div class="flex flex-col lg:row-span-1 min-h-[250px] lg:min-h-0">
            <h3 class="text-sm font-bold text-text-heading mb-2 uppercase tracking-wider shrink-0">
              Distribuição por Status
            </h3>
            <StatusDonutChart
              v-if="store.summary"
              :summary="store.summary"
              class="flex-1 min-h-0"
            />
            <p v-else class="text-sm text-text-muted text-center py-4">Carregando...</p>
          </div>

          <!-- Bottom Right: Últimos Alertas -->
          <div class="flex flex-col lg:row-span-1 min-h-[300px] lg:min-h-0">
            <h3 class="text-sm font-bold text-text-heading mb-2 uppercase tracking-wider shrink-0">
              Últimos Alertas
            </h3>
            <div class="flex-1 w-full min-h-0 overflow-y-auto pr-2">
              <RecentAlerts :alerts="store.recentAlerts" />
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
