<script setup lang="ts">
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

const store = useDashboardStore()

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
  <div class="animate-fade-in flex flex-col h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] space-y-3 min-h-0">
    <div class="shrink-0">
      <h1 class="text-2xl font-bold text-text-heading leading-tight">Dashboard</h1>
      <p class="text-sm text-text-muted">Visão geral do sistema de monitoramento</p>
    </div>

    <!-- Layout 2x2: Gráfico | Mapa // Status | Alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-3 gap-4 flex-1 min-h-0">
      
      <!-- Top Left: Gráfico -->
      <BaseCard title="Consumo Diário (últimos 30 dias)" class="flex flex-col lg:row-span-2 min-h-0">
        <div class="flex-1 w-full min-h-0">
          <ConsumptionChart v-if="store.consumption.length" :data="store.consumption" />
          <p v-else class="text-sm text-text-muted text-center py-8">Carregando dados...</p>
        </div>
      </BaseCard>

      <!-- Top Right: Mapa -->
      <BaseCard title="Mapa de Hidrômetros" class="flex flex-col lg:row-span-2 min-h-0">
        <!-- Cards super compactos no topo do mapa -->
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-2 mb-3">
          <div
            v-for="card in summaryCards"
            :key="card.key"
            class="flex items-center gap-2 bg-surface/50 rounded-lg p-2 border border-border/50"
          >
            <div :class="['flex h-7 w-7 shrink-0 items-center justify-center rounded-md', card.bg]">
              <component :is="card.icon" :class="['h-3.5 w-3.5', card.color]" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold text-text-heading leading-none">
                {{ store.summary?.[card.key as keyof typeof store.summary] ?? '—' }}
              </span>
              <span class="text-[10px] text-text-muted truncate leading-tight mt-0.5">{{ card.label }}</span>
            </div>
          </div>
        </div>
        
        <div class="flex-1 w-full min-h-0">
          <MapView :hydrometers="store.mapHydrometers" />
        </div>
      </BaseCard>

      <!-- Bottom Left: Donut Chart -->
      <BaseCard title="Distribuição por Status" class="flex flex-col lg:row-span-1 min-h-0">
        <StatusDonutChart v-if="store.summary" :summary="store.summary" />
        <p v-else class="text-sm text-text-muted text-center py-4">Carregando...</p>
      </BaseCard>

      <!-- Bottom Right: Últimos Alertas -->
      <BaseCard title="Últimos Alertas" class="flex flex-col lg:row-span-1 min-h-0">
        <RecentAlerts :alerts="store.recentAlerts" />
      </BaseCard>

    </div>
  </div>
</template>
