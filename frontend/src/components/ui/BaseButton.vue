<script setup lang="ts">
/**
 * Botão reutilizável com variantes visuais.
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
      'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-200 cursor-pointer',
      'focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:ring-offset-2 focus:ring-offset-surface',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      {
        'bg-primary-600 text-white hover:bg-primary-500 active:bg-primary-700':
          variant === 'primary',
        'bg-surface-hover text-text-body hover:bg-slate-600 active:bg-slate-700':
          variant === 'secondary',
        'bg-danger text-white hover:bg-red-500 active:bg-red-700': variant === 'danger',
        'bg-transparent text-text-body hover:bg-surface-hover hover:text-text-heading':
          variant === 'ghost',
      },
      {
        'px-3 py-1.5 text-sm': size === 'sm',
        'px-4 py-2 text-sm': size === 'md',
        'px-6 py-3 text-base': size === 'lg',
      },
    ]"
  >
    <!-- Spinner de carregamento -->
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4"
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
