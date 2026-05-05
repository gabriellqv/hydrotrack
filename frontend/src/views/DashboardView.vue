<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import ConsumptionChart from '@/components/ConsumptionChart.vue'
import MapView from '@/components/MapView.vue'
import { Droplets, Wifi, WifiOff, AlertTriangle, BarChart3, Bell } from 'lucide-vue-next'

/**
 * View Principal do Dashboard.
 *
 * Consolida as três principais métricas do sistema: resumo em cards,
 * gráfico de evolução do consumo e mapa interativo. Todos os dados são
 * cacheados/gerenciados pelo DashboardStore.
 */

const store = useDashboardStore()

onMounted(async () => {
  await Promise.all([store.fetchSummary(), store.fetchConsumption(), store.fetchMap()])
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
  <div class="animate-fade-in space-y-8">
    <div>
      <h1 class="text-2xl font-bold text-text-heading">Dashboard</h1>
      <p class="text-sm text-text-muted mt-1">Visão geral do sistema de monitoramento</p>
    </div>

    <!-- Cards de resumo -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      <BaseCard v-for="card in summaryCards" :key="card.key" compact hoverable>
        <div class="flex flex-col items-center text-center gap-2">
          <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', card.bg]">
            <component :is="card.icon" :class="['h-5 w-5', card.color]" />
          </div>
          <span class="text-2xl font-bold text-text-heading">
            {{ store.summary?.[card.key as keyof typeof store.summary] ?? '—' }}
          </span>
          <span class="text-xs text-text-muted">{{ card.label }}</span>
        </div>
      </BaseCard>
    </div>

    <!-- Gráfico de consumo -->
    <BaseCard title="Consumo Diário (últimos 30 dias)" class="min-w-0">
      <ConsumptionChart v-if="store.consumption.length" :data="store.consumption" />
      <p v-else class="text-sm text-text-muted text-center py-12">Carregando dados...</p>
    </BaseCard>

    <!-- Preview do mapa -->
    <BaseCard title="Mapa de Hidrômetros">
      <MapView :hydrometers="store.mapHydrometers" />
    </BaseCard>
  </div>
</template>
