<script setup lang="ts">
import { computed, onUnmounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  type ChartOptions,
  type TooltipModel,
} from 'chart.js'
import type { DashboardSummary } from '@/types'

/**
 * Gráfico Donut de distribuição de status da rede.
 *
 * Exibe a proporção de dispositivos Online, Offline e Em Alerta
 * com miolo vazado tecnológico e indicador central de total de ativos.
 *
 * @prop {DashboardSummary} summary - Dados de resumo do dashboard
 */
const props = defineProps<{
  summary: DashboardSummary
}>()

ChartJS.register(ArcElement, Tooltip, Legend)

const total = computed(
  () => (props.summary?.online || 0) + (props.summary?.offline || 0) + (props.summary?.alert || 0),
)

const onlinePct = computed(() =>
  total.value > 0 ? (((props.summary?.online || 0) / total.value) * 100).toFixed(0) : '0',
)

const statusItems = computed(() => [
  {
    label: 'Online',
    value: props.summary?.online || 0,
    pct: total.value > 0 ? (((props.summary?.online || 0) / total.value) * 100).toFixed(1) : '0',
    color: '#22c55e',
    dotClass: 'bg-emerald-500 shadow-[0_0_8px_rgba(34,197,94,0.4)]',
    badgeBg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
  },
  {
    label: 'Offline',
    value: props.summary?.offline || 0,
    pct: total.value > 0 ? (((props.summary?.offline || 0) / total.value) * 100).toFixed(1) : '0',
    color: '#94a3b8',
    dotClass: 'bg-slate-400 shadow-[0_0_8px_rgba(148,163,184,0.3)]',
    badgeBg: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
  },
  {
    label: 'Em Alerta',
    value: props.summary?.alert || 0,
    pct: total.value > 0 ? (((props.summary?.alert || 0) / total.value) * 100).toFixed(1) : '0',
    color: '#ef4444',
    dotClass: 'bg-rose-500 shadow-[0_0_8px_rgba(239,68,68,0.4)]',
    badgeBg: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
  },
])

const chartData = computed(() => ({
  labels: ['Online', 'Offline', 'Em Alerta'],
  datasets: [
    {
      data: [props.summary?.online || 0, props.summary?.offline || 0, props.summary?.alert || 0],
      backgroundColor: [
        'rgba(34, 197, 94, 0.75)', // Verde Esmeralda
        'rgba(148, 163, 184, 0.45)', // Cinza Neutro
        'rgba(244, 63, 94, 0.75)', // Rosa / Vermelho
      ],
      borderColor: [
        'rgba(34, 197, 94, 0.95)',
        'rgba(148, 163, 184, 0.7)',
        'rgba(244, 63, 94, 0.95)',
      ],
      borderWidth: 2,
      borderRadius: 5,
      spacing: 3,
      hoverOffset: 6,
    },
  ],
}))

const externalTooltipHandler = (context: { chart: ChartJS; tooltip: TooltipModel<'doughnut'> }) => {
  const { chart, tooltip } = context
  let tooltipEl = chart.canvas.parentElement?.querySelector(
    '.donut-html-tooltip',
  ) as HTMLElement | null

  if (!tooltipEl && chart.canvas.parentElement) {
    tooltipEl = document.createElement('div')
    tooltipEl.className =
      'donut-html-tooltip pointer-events-none absolute z-50 transition-all duration-150 ease-out select-none'
    chart.canvas.parentElement.appendChild(tooltipEl)
  }

  if (!tooltipEl) return

  if (tooltip.opacity === 0) {
    tooltipEl.style.opacity = '0'
    return
  }

  if (tooltip.body) {
    const title = tooltip.title?.[0] || ''
    const bodyLines = tooltip.body.map((b) => b.lines).flat()
    const color = String(
      tooltip.labelColors?.[0]?.borderColor ||
        tooltip.labelColors?.[0]?.backgroundColor ||
        '#38bdf8',
    )

    let html = `
      <div class="px-2.5 py-1.5 rounded-xl bg-white/95 dark:bg-[#0c1322]/95 backdrop-blur-md border border-slate-200/90 dark:border-white/10 shadow-xl shadow-slate-900/10 dark:shadow-black/50 text-xs flex flex-col gap-0.5">
        <div class="font-bold text-[11px] text-slate-500 dark:text-slate-300 flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full shrink-0 shadow-sm" style="background-color: ${color}; box-shadow: 0 0 6px ${color};"></span>
          <span>${title}</span>
        </div>
    `
    bodyLines.forEach((line: string) => {
      html += `<div class="text-xs font-bold text-slate-900 dark:text-white whitespace-nowrap pl-3.5">${line}</div>`
    })
    html += `</div>`
    tooltipEl.innerHTML = html
  }

  const positionX = chart.canvas.offsetLeft + tooltip.caretX
  const positionY = chart.canvas.offsetTop + tooltip.caretY

  tooltipEl.style.opacity = '1'
  tooltipEl.style.left = `${positionX}px`
  tooltipEl.style.top = `${positionY}px`

  let translateX = '-50%'
  let translateY = '-115%'

  if (tooltip.caretX < 60) {
    translateX = '-15%'
  } else if (tooltip.caretX > chart.width - 60) {
    translateX = '-85%'
  }

  if (tooltip.caretY < 40) {
    translateY = '-100%'
  }

  tooltipEl.style.transform = `translate(${translateX}, ${translateY})`
}

onUnmounted(() => {
  const tooltips = document.querySelectorAll('.donut-html-tooltip')
  tooltips.forEach((el) => el.remove())
})

const chartOptions: ChartOptions<'doughnut'> = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  animation: {
    animateRotate: true,
    animateScale: true,
    duration: 1000,
    easing: 'easeOutQuart',
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      enabled: false,
      external: externalTooltipHandler,
      callbacks: {
        title: (items) => items[0]?.label || '',
        label: (ctx) => {
          const totalVal = (ctx.dataset.data as number[]).reduce((a, b) => a + b, 0)
          const pct = totalVal > 0 ? (((ctx.parsed as number) / totalVal) * 100).toFixed(1) : '0'
          const count = ctx.parsed as number
          const unit = count === 1 ? 'dispositivo' : 'dispositivos'
          return `${count.toLocaleString('pt-BR')} ${unit} (${pct}%)`
        },
      },
    },
  },
}
</script>

<template>
  <div
    class="flex flex-col sm:flex-row items-center justify-between gap-5 sm:gap-6 w-full h-full min-h-0 py-1"
  >
    <!-- Gráfico Donut com Métrica Central -->
    <div
      class="relative w-44 h-44 sm:w-48 sm:h-48 lg:w-52 lg:h-52 shrink-0 flex items-center justify-center"
    >
      <Doughnut :data="chartData" :options="chartOptions" />

      <!-- Miolo com Métrica Central -->
      <div
        class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none text-center"
      >
        <span class="text-2xl lg:text-3xl font-black text-text-heading tracking-tight leading-none">
          <AnimatedCounter :value="total" />
        </span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted mt-1">
          Hidrômetros
        </span>
        <span class="text-[10px] font-extrabold text-emerald-400 mt-0.5">
          {{ onlinePct }}% Ativos
        </span>
      </div>
    </div>

    <!-- Legenda Lateral com Cards Compactos -->
    <div class="flex-1 w-full flex flex-col justify-center gap-2 sm:gap-2.5 sm:pl-2">
      <div
        v-for="item in statusItems"
        :key="item.label"
        class="flex items-center justify-between p-2 sm:p-2.5 rounded-xl bg-surface/40 hover:bg-surface/60 border border-border/40 transition-colors"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <span :class="['w-2.5 h-2.5 rounded-full shrink-0', item.dotClass]"></span>
          <span class="text-xs font-semibold text-text-heading truncate">{{ item.label }}</span>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <span class="text-sm font-bold text-text-heading tabular-nums">
            <AnimatedCounter :value="item.value" />
          </span>
          <span
            :class="['text-[10px] font-extrabold px-1.5 py-0.5 rounded-md border', item.badgeBg]"
          >
            {{ item.pct }}%
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
