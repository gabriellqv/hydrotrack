<script setup lang="ts">
import type { Alert } from '@/types'
import { AlertTriangle, WifiOff, Flame } from 'lucide-vue-next'

/**
 * Lista compacta dos alertas mais recentes do sistema.
 *
 * Exibe ícone por tipo, mensagem truncada e timestamp relativo,
 * oferecendo um "feed ao vivo" de eventos no dashboard.
 *
 * @prop {Alert[]} alerts - Array de alertas (já ordenados por data)
 */
defineProps<{
  alerts: Alert[]
}>()

/** Mapeia tipo do alerta para ícone e cor */
const alertConfig: Record<string, { icon: typeof AlertTriangle; color: string }> = {
  high_consumption: { icon: Flame, color: 'text-red-400 bg-red-500/15' },
  offline: { icon: WifiOff, color: 'text-slate-400 bg-slate-500/15' },
  zero_reading: { icon: AlertTriangle, color: 'text-amber-400 bg-amber-500/15' },
}

/**
 * Retorna representação relativa do timestamp: 'agora', '{minutos}min', '{horas}h' ou '{dias}d'.
 *
 * @param dateStr - Timestamp ISO.
 */
function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'agora'
  if (mins < 60) return `${mins}min`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h`
  return `${Math.floor(hrs / 24)}d`
}
</script>

<template>
  <div class="space-y-2 overflow-y-auto h-full pr-1">
    <div
      v-for="alert in alerts"
      :key="alert.id"
      class="flex items-start gap-3 rounded-lg bg-surface/50 px-3 py-2.5 border border-border/50 transition-colors hover:border-border-hover"
    >
      <div
        :class="[
          'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
          alertConfig[alert.type]?.color || 'text-slate-400 bg-slate-500/15',
        ]"
      >
        <component :is="alertConfig[alert.type]?.icon || AlertTriangle" class="h-4 w-4" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm text-text-body truncate">{{ alert.message }}</p>
        <p class="text-xs text-text-muted mt-0.5">{{ timeAgo(alert.created_at) }}</p>
      </div>
    </div>
    <p v-if="!alerts.length" class="text-sm text-text-muted text-center py-6">
      Nenhum alerta recente.
    </p>
  </div>
</template>
