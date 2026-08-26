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
  type ChartOptions,
  type ChartData,
} from 'chart.js'
import type { ConsumptionPoint } from '@/types'
import { useTheme } from '@/composables/useTheme'

/**
 * Gráfico de linha de consumo de água.
 *
 * Renderiza a evolução do consumo em m³ ao longo do período selecionado,
 * respeitando tema claro/escuro e aplicando animação de entrada controlada.
 *
 * @prop {ConsumptionPoint[]} data - Array de pontos {date, total_m3}.
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

const chartOptions: ChartOptions<'line'> = {
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

/**
 * Dados exibidos pelo gráfico. Inicia vazio para produzir animação de crescimento ao montar.
 */
const displayedData = ref<ChartData<'line'>>({
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
  // Aplica os dados após pequeno delay para ativar a animação de entrada do Chart.js.
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
