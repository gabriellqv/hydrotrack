<script setup lang="ts">
import BaseBadge from './ui/BaseBadge.vue'

/**
 * Badge especializado para o status de hidrômetros.
 *
 * Mapeia os status do backend (online/offline/alert) para
 * as variantes visuais do BaseBadge com labels em pt-BR.
 *
 * @prop {'online' | 'offline' | 'alert'} status - Status do hidrômetro
 */
const props = defineProps<{
  status: 'online' | 'offline' | 'alert'
}>()

const statusMap = {
  online: { variant: 'success' as const, label: 'Online' },
  offline: { variant: 'muted' as const, label: 'Offline' },
  alert: { variant: 'danger' as const, label: 'Alerta' },
}
</script>

<template>
  <BaseBadge :variant="statusMap[props.status].variant">
    {{ statusMap[props.status].label }}
  </BaseBadge>
</template>
