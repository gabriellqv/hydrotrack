<script setup lang="ts">
import { computed } from 'vue'
import { Pie } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import type { DashboardSummary } from '@/types'

/**
 * Gráfico de pizza com legenda custom para distribuição de status.
 *
 * Mostra a proporção de dispositivos Online, Offline e Em Alerta,
 * com legenda HTML rica exibindo valor absoluto e percentual.
 *
 * @prop {DashboardSummary} summary - Dados de resumo do dashboard
 */
const props = defineProps<{
  summary: DashboardSummary
}>()

ChartJS.register(ArcElement, Tooltip, Legend)

const statusItems = computed(() => {
  const total = props.summary.online + props.summary.offline + props.summary.alert
  return [
    {
      label: 'Online',
      value: props.summary.online,
      pct: total > 0 ? ((props.summary.online / total) * 100).toFixed(1) : '0',
      color: '#22c55e',
      dotClass: 'bg-green-500',
    },
    {
      label: 'Offline',
      value: props.summary.offline,
      pct: total > 0 ? ((props.summary.offline / total) * 100).toFixed(1) : '0',
      color: '#64748b',
      dotClass: 'bg-slate-500',
    },
    {
      label: 'Em Alerta',
      value: props.summary.alert,
      pct: total > 0 ? ((props.summary.alert / total) * 100).toFixed(1) : '0',
      color: '#ef4444',
      dotClass: 'bg-red-500',
    },
  ]
})

const chartData = computed(() => ({
  labels: ['Online', 'Offline', 'Em Alerta'],
  datasets: [
    {
      data: [props.summary.online, props.summary.offline, props.summary.alert],
      backgroundColor: ['#22c55e', '#64748b', '#ef4444'],
      borderColor: 'transparent',
      borderWidth: 0,
      hoverOffset: 6,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  layout: {
    padding: 2,
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx: { label: string; parsed: number; dataset: { data: number[] } }) => {
          const total = ctx.dataset.data.reduce((a: number, b: number) => a + b, 0)
          const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0'
          return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`
        },
      },
    },
  },
}
</script>

<template>
  <div class="flex items-center gap-2 h-full min-h-0 -my-4">
    <!-- Gráfico -->
    <div class="relative flex-1 h-full min-h-0 flex items-center justify-center">
      <Pie :data="chartData" :options="chartOptions" />
    </div>

    <!-- Legenda Custom -->
    <div class="flex flex-col gap-3 shrink-0">
      <div v-for="item in statusItems" :key="item.label" class="flex items-center gap-3">
        <span :class="['w-3 h-3 rounded-full shrink-0', item.dotClass]"></span>
        <div class="flex flex-col">
          <span class="text-sm font-semibold text-text-heading">
            {{ item.value }}
            <span class="text-xs font-normal text-text-muted ml-1">({{ item.pct }}%)</span>
          </span>
          <span class="text-xs text-text-muted">{{ item.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
