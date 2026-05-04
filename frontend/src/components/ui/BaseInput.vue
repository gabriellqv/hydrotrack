<script setup lang="ts">
/**
 * Input reutilizável com label, erro e ícone opcional.
 *
 * @prop {string} label - Label do campo
 * @prop {string} modelValue - Valor do v-model
 * @prop {string} type - Tipo do input (text, email, password, number)
 * @prop {string} placeholder - Texto placeholder
 * @prop {string} error - Mensagem de erro de validação
 * @prop {boolean} disabled - Desabilita o campo
 */
defineProps<{
  label?: string
  modelValue: string | number
  type?: string
  placeholder?: string
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()
</script>

<template>
  <div class="space-y-1.5">
    <label v-if="label" class="block text-sm font-medium text-slate-300">
      {{ label }}
    </label>

    <div class="relative">
      <slot name="icon" />
      <input
        :type="type ?? 'text'"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        :class="[
          'w-full rounded-lg border bg-surface-card px-4 py-2.5 text-sm text-slate-100',
          'placeholder:text-slate-500 transition-colors duration-200',
          'focus:outline-none focus:ring-2 focus:ring-primary-500/50',
          'disabled:opacity-50 disabled:cursor-not-allowed',
          error
            ? 'border-danger focus:border-danger'
            : 'border-slate-700 focus:border-primary-500 hover:border-slate-600',
          $slots.icon ? 'pl-10' : '',
        ]"
      />
    </div>

    <p v-if="error" class="text-xs text-danger mt-1">{{ error }}</p>
  </div>
</template>
