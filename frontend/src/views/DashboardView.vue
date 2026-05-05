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
  <div class="animate-fade-in space-y-3">
    <div>
      <h1 class="text-2xl font-bold text-text-heading">Dashboard</h1>
      <p class="text-sm text-text-muted mt-1">Visão geral do sistema de monitoramento</p>
    </div>

    <!-- Layout Principal: 2 Colunas (Esquerda: Dados / Direita: Visuais) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
      
      <!-- Coluna Esquerda: Métricas, Status e Alertas (Span 4) -->
      <div class="lg:col-span-4 flex flex-col gap-4">
        <!-- Cards de resumo (Grid 2x3) -->
        <div class="grid grid-cols-2 gap-3">
          <BaseCard v-for="card in summaryCards" :key="card.key" compact hoverable>
            <div class="flex flex-col justify-center gap-1">
              <div class="flex items-center gap-2">
                <div :class="['flex h-6 w-6 shrink-0 items-center justify-center rounded-md', card.bg]">
                  <component :is="card.icon" :class="['h-3 w-3', card.color]" />
                </div>
                <span class="text-xs text-text-muted truncate">{{ card.label }}</span>
              </div>
              <span class="text-xl font-bold text-text-heading leading-tight ml-1">
                {{ store.summary?.[card.key as keyof typeof store.summary] ?? '—' }}
              </span>
            </div>
          </BaseCard>
        </div>

        <!-- Donut Chart -->
        <BaseCard title="Distribuição por Status" class="flex flex-col">
          <StatusDonutChart v-if="store.summary" :summary="store.summary" />
          <p v-else class="text-sm text-text-muted text-center py-4">Carregando...</p>
        </BaseCard>

        <!-- Últimos Alertas -->
        <BaseCard title="Últimos Alertas" class="flex flex-col flex-1">
          <RecentAlerts :alerts="store.recentAlerts" />
        </BaseCard>
      </div>

      <!-- Coluna Direita: Gráfico e Mapa (Span 8) -->
      <div class="lg:col-span-8 flex flex-col gap-4">
        <BaseCard title="Consumo Diário (últimos 30 dias)" class="flex flex-col h-auto">
          <div class="w-full">
            <ConsumptionChart v-if="store.consumption.length" :data="store.consumption" />
            <p v-else class="text-sm text-text-muted text-center py-8">Carregando dados...</p>
          </div>
        </BaseCard>

        <BaseCard title="Mapa de Hidrômetros" class="flex flex-col flex-1">
          <div class="h-full w-full">
            <MapView :hydrometers="store.mapHydrometers" />
          </div>
        </BaseCard>
      </div>

    </div>
  </div>
</template>
