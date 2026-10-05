<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'

/**
 * AnimatedCounter.vue
 *
 * Componente de animação suave de contagem numérica progressiva (count-up).
 * Utiliza requestAnimationFrame com curva de atenuação exponencial (ease-out expo)
 * para um efeito visual elegante, fluido e de alta performance a 60/120fps.
 */
const props = withDefaults(
  defineProps<{
    value: number | string | null | undefined
    duration?: number
    formatter?: (val: number) => string
  }>(),
  {
    duration: 1200,
    formatter: (val: number) => Math.round(val).toLocaleString('pt-BR'),
  },
)

const displayValue = ref<string>('0')
let animationFrameId: number | null = null

/** Função de atenuação suave exponencial (easeOutExpo) */
function easeOutExpo(x: number): number {
  return x === 1 ? 1 : 1 - Math.pow(2, -10 * x)
}

function parseVal(input: unknown): number | null {
  if (typeof input === 'number') return isNaN(input) ? null : input
  if (typeof input === 'string') {
    const parsed = parseFloat(input.replace(/[^\d.-]/g, ''))
    return isNaN(parsed) ? null : parsed
  }
  return null
}

function runAnimation(startVal: number, endVal: number) {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
    animationFrameId = null
  }

  const startTime = performance.now()
  const diff = endVal - startVal

  function step(now: number) {
    const elapsed = now - startTime
    const progress = Math.min(elapsed / props.duration, 1)
    const easedProgress = easeOutExpo(progress)
    const current = startVal + diff * easedProgress

    displayValue.value = props.formatter(current)

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(step)
    } else {
      displayValue.value = props.formatter(endVal)
      animationFrameId = null
    }
  }

  animationFrameId = requestAnimationFrame(step)
}

function initOrUpdate(oldVal?: unknown) {
  const target = parseVal(props.value)
  if (target === null) {
    displayValue.value =
      props.value !== null && props.value !== undefined ? String(props.value) : '—'
    return
  }

  const start = parseVal(oldVal) ?? 0
  runAnimation(start, target)
}

onMounted(() => {
  initOrUpdate(0)
})

watch(
  () => props.value,
  (newVal, oldVal) => {
    initOrUpdate(oldVal)
  },
)

onUnmounted(() => {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
  }
})
</script>

<template>
  <span class="inline-block tabular-nums transition-colors">{{ displayValue }}</span>
</template>
