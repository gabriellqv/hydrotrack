<script setup lang="ts">
import BaseBadge from './ui/BaseBadge.vue'
import BaseButton from './ui/BaseButton.vue'
import type { Alert } from '@/types'
import { useIsAdmin } from '@/composables/useIsAdmin'

/**
 * Card de alerta individual com ação de resolução.
 *
 * Exibe o tipo do alerta, a mensagem, o timestamp e um botão
 * para marcar como resolvido (visível apenas para admins).
 *
 * @prop {Alert} alert - Dados do alerta
 * @emits resolve - Emitido quando o admin clica em "Resolver"
 */
defineProps<{
  alert: Alert
}>()

const emit = defineEmits<{
  resolve: [id: number]
}>()

const { isAdmin } = useIsAdmin()

const typeMap: Record<string, { variant: 'danger' | 'warning' | 'muted'; label: string }> = {
  high_consumption: { variant: 'danger', label: 'Consumo Alto' },
  zero_reading: { variant: 'warning', label: 'Leitura Zero' },
  offline: { variant: 'muted', label: 'Sem Comunicação' },
}
</script>

<template>
  <div
    :class="[
      'flex items-start gap-4 rounded-lg border p-4 transition-colors',
      alert.resolved
        ? 'border-slate-700/30 bg-surface opacity-60'
        : 'border-slate-700/50 bg-surface-card hover:border-slate-600',
    ]"
  >
    <!-- Conteúdo -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 mb-1">
        <BaseBadge :variant="typeMap[alert.type]?.variant ?? 'muted'">
          {{ typeMap[alert.type]?.label ?? alert.type }}
        </BaseBadge>
        <span v-if="alert.resolved" class="text-xs text-green-400">✓ Resolvido</span>
      </div>

      <p class="text-sm text-slate-300 truncate">{{ alert.message }}</p>

      <p class="text-xs text-slate-500 mt-1">
        {{ new Date(alert.created_at).toLocaleString('pt-BR') }}
      </p>
    </div>

    <!-- Ação -->
    <BaseButton
      v-if="isAdmin && !alert.resolved"
      variant="ghost"
      size="sm"
      @click="emit('resolve', alert.id)"
    >
      Resolver
    </BaseButton>
  </div>
</template>
