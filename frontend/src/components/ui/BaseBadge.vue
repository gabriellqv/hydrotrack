<script setup lang="ts">
/**
 * Badge/pill de status com cores semânticas.
 *
 * @prop {'success' | 'warning' | 'danger' | 'muted' | 'info'} variant - Variante visual
 */
withDefaults(
  defineProps<{
    variant?: 'success' | 'warning' | 'danger' | 'muted' | 'info'
  }>(),
  {
    variant: 'muted',
  },
)
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
      {
        'bg-green-500/15 text-green-400': variant === 'success',
        'bg-amber-500/15 text-amber-400': variant === 'warning',
        'bg-red-500/15 text-red-400': variant === 'danger',
        'bg-slate-500/15 text-slate-400': variant === 'muted',
        'bg-blue-500/15 text-blue-400': variant === 'info',
      },
    ]"
  >
    <!-- Dot indicador -->
    <span
      :class="[
        'h-1.5 w-1.5 rounded-full',
        {
          'bg-green-400': variant === 'success',
          'bg-amber-400': variant === 'warning',
          'bg-red-400': variant === 'danger',
          'bg-slate-400': variant === 'muted',
          'bg-blue-400': variant === 'info',
        },
      ]"
    />
    <slot />
  </span>
</template>
