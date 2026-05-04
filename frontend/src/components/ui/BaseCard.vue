<script setup lang="ts">
/**
 * Card container reutilizável com efeito glassmorphism sutil.
 *
 * @prop {string} title - Título opcional do card
 * @prop {boolean} hoverable - Aplica efeito hover elevado
 * @prop {boolean} compact - Reduz o padding interno
 */
withDefaults(
  defineProps<{
    title?: string
    hoverable?: boolean
    compact?: boolean
  }>(),
  {
    hoverable: false,
    compact: false,
  },
)
</script>

<template>
  <div
    :class="[
      'rounded-xl border border-slate-700/50 bg-surface-card shadow-card',
      'transition-all duration-300',
      hoverable
        ? 'hover:border-primary-500/30 hover:shadow-primary-500/5 hover:-translate-y-0.5'
        : '',
      compact ? 'p-4' : 'p-6',
    ]"
  >
    <h3 v-if="title" class="text-lg font-semibold text-slate-100 mb-4">
      {{ title }}
    </h3>
    <slot />
  </div>
</template>
