<script setup lang="ts">
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import type { DashboardSummary } from '@/types'

/**
 * Gráfico de rosca que exibe a distribuição de status dos hidrômetros.
 *
 * Mostra a proporção de dispositivos Online, Offline e Em Alerta,
 * oferecendo uma visão rápida da saúde da rede de sensores.
 *
 * @prop {DashboardSummary} summary - Dados de resumo do dashboard
 */
const props = defineProps<{
  summary: DashboardSummary
}>()

ChartJS.register(ArcElement, Tooltip, Legend)

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
  cutout: '65%',
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        color: '#94a3b8',
        padding: 16,
        usePointStyle: true,
        pointStyleWidth: 8,
        font: { size: 12 },
      },
    },
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
  <div class="relative w-full h-full" style="min-height: 14rem">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>
