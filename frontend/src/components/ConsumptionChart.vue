<script setup lang="ts">
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Filler,
  type TooltipItem,
  type ChartOptions,
} from 'chart.js'
import type { ConsumptionPoint } from '@/types'
import { useTheme } from '@/composables/useTheme'

/**
 * Gráfico de linha que exibe o consumo hídrico diário acumulado.
 *
 * Reativo a mudanças de período (7d, 30d, 90d) com interpolação suave
 * e suporte nativo aos temas claro e escuro.
 *
 * @prop {ConsumptionPoint[]} data - Array de pontos {date, total_m3}
 */
const props = defineProps<{
  data: ConsumptionPoint[]
}>()

const { isDark } = useTheme()

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler)

const chartData = computed(() => {
  const primaryColor = isDark.value ? '#60a5fa' : '#2563eb'

  return {
    labels: props.data.map((p) => {
      const date = new Date(p.date)
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
    }),
    datasets: [
      {
        label: 'Consumo (m³)',
        data: props.data.map((p) => {
          const val =
            typeof p.total_m3 === 'number'
              ? p.total_m3
              : parseFloat(p.total_m3 as unknown as string)
          return isNaN(val) ? 0 : val
        }),
        borderColor: primaryColor,
        backgroundColor: `${primaryColor}1f`,
        fill: true,
        tension: 0.35,
        pointRadius: props.data.length > 30 ? 2 : 4,
        pointHoverRadius: 6,
        pointBackgroundColor: primaryColor,
        pointBorderColor: isDark.value ? '#111924' : '#ffffff',
        pointBorderWidth: 1.5,
        borderWidth: 2.5,
      },
    ],
  }
})

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 750,
    easing: 'easeOutQuart',
  },
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    tooltip: {
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
      titleColor: isDark.value ? '#f1f5f9' : '#0f172a',
      bodyColor: isDark.value ? '#cbd5e1' : '#334155',
      borderColor: isDark.value ? 'rgba(51, 65, 85, 0.6)' : 'rgba(203, 213, 225, 0.8)',
      borderWidth: 1,
      padding: 10,
      boxPadding: 4,
      usePointStyle: true,
      callbacks: {
        label: (ctx: TooltipItem<'line'>) => {
          const val = ctx.parsed.y ?? 0
          return ` Consumo: ${val.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} m³`
        },
      },
    },
    legend: { display: false },
  },
  scales: {
    x: {
      grid: {
        color: isDark.value ? 'rgba(51, 65, 85, 0.2)' : 'rgba(203, 213, 225, 0.4)',
      },
      ticks: {
        color: isDark.value ? '#94a3b8' : '#64748b',
        font: { size: 11 },
        maxRotation: 45,
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        color: isDark.value ? 'rgba(51, 65, 85, 0.2)' : 'rgba(203, 213, 225, 0.4)',
      },
      ticks: {
        color: isDark.value ? '#94a3b8' : '#64748b',
        font: { size: 11 },
        callback: (val) => `${Number(val).toLocaleString('pt-BR')} m³`,
      },
    },
  },
}))
</script>

<template>
  <div class="relative w-full h-full min-h-0">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
