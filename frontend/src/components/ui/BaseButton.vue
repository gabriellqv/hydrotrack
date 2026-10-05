<script setup lang="ts">
/**
 * Botão base do HydroTrack, em formato pílula.
 *
 * @prop {'primary' | 'secondary' | 'danger' | 'ghost'} variant - Estilo visual
 * @prop {'sm' | 'md' | 'lg'} size - Tamanho do botão
 * @prop {boolean} loading - Exibe spinner e desabilita cliques
 * @prop {boolean} disabled - Desabilita o botão
 */
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
    size?: 'sm' | 'md' | 'lg'
    loading?: boolean
    disabled?: boolean
  }>(),
  {
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
  },
)
</script>

<template>
  <button
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center rounded-full font-semibold transition-all duration-200 cursor-pointer select-none active:scale-[0.98]',
      'focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:ring-offset-2 focus:ring-offset-surface',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
      {
        'bg-primary-600 text-white hover:bg-primary-500 active:bg-primary-700 shadow-md shadow-primary-900/25 border border-transparent':
          variant === 'primary',
        'bg-surface-card hover:bg-surface-hover active:bg-surface-card text-text-heading border border-border/80 hover:border-border-hover shadow-sm':
          variant === 'secondary',
        'bg-rose-500/10 hover:bg-rose-500/20 active:bg-rose-500/30 text-rose-400 hover:text-rose-300 border border-rose-500/30 shadow-sm':
          variant === 'danger',
        'bg-transparent text-text-body hover:bg-surface-hover hover:text-text-heading border border-transparent':
          variant === 'ghost',
      },
      {
        'px-3.5 py-1.5 text-xs gap-1.5': size === 'sm',
        'px-4.5 py-2 text-sm gap-2': size === 'md',
        'px-6 py-2.5 text-base gap-2.5': size === 'lg',
      },
    ]"
  >
    <!-- Spinner de carregamento -->
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4 shrink-0"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
      />
    </svg>

    <slot />
  </button>
</template>
