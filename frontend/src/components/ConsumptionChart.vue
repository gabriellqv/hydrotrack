<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
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
} from 'chart.js'
import type { ConsumptionPoint } from '@/types'
import { useTheme } from '@/composables/useTheme'
/**
 * Gráfico de linha que exibe o consumo hídrico diário acumulado.
 *
 * Usa a biblioteca Chart.js via vue-chartjs para renderizar
 * a evolução do consumo em m³ ao longo do período selecionado.
 *
 * @prop {ConsumptionPoint[]} data - Array de pontos {date, total_m3}
 */
const props = defineProps<{
  data: ConsumptionPoint[]
}>()

const { isDark } = useTheme()

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler)

const fullChartData = computed(() => {
  const style = getComputedStyle(document.documentElement)
  const primaryColor = style.getPropertyValue('--color-primary-500').trim() || '#3b82f6'

  return {
    labels: props.data.map((p) => {
      const date = new Date(p.date)
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
    }),
    datasets: [
      {
        label: 'Consumo (m³)',
        data: props.data.map((p) => p.total_m3),
        borderColor: primaryColor,
        backgroundColor: `${primaryColor}1a`,
        fill: isDark.value,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 6,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 2000,
    easing: 'easeOutQuart',
  },
  plugins: {
    tooltip: {
      callbacks: {
        label: (ctx: TooltipItem<'line'>) => `${(ctx.parsed.y ?? 0).toFixed(3)} m³`,
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      title: { display: true, text: 'Consumo (m³)' },
    },
  },
}

const displayedData = ref({
  labels: [],
  datasets: [
    {
      label: 'Consumo (m³)',
      data: [],
      borderColor: '#3b82f6',
      backgroundColor: '#3b82f61a',
      fill: isDark.value,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 6,
    },
  ],
})

onMounted(() => {
  setTimeout(() => {
    displayedData.value = fullChartData.value
  }, 400)
})
</script>

<template>
  <div class="relative w-full h-full min-h-0">
    <Line :data="displayedData" :options="chartOptions" />
  </div>
</template>
