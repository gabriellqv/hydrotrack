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
} from 'chart.js'
import type { ConsumptionPoint } from '@/types'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler)

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

const chartData = computed(() => ({
  labels: props.data.map((p) => {
    const date = new Date(p.date)
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
  }),
  datasets: [
    {
      label: 'Consumo (m³)',
      data: props.data.map((p) => p.total_m3),
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 6,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    tooltip: {
      callbacks: {
        label: (ctx: any) => `${ctx.parsed.y.toFixed(3)} m³`,
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
</script>

<template>
  <div class="h-[350px]">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
