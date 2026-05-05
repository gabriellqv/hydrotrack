<script setup lang="ts">
import { onMounted } from 'vue'
import { useAlertStore } from '@/stores/alert'
import BaseCard from '@/components/ui/BaseCard.vue'
import AlertItem from '@/components/AlertItem.vue'

/**
 * View de Alertas.
 *
 * Exibe a listagem de todos os alertas do sistema (anomalias de telemetria
 * e falhas de comunicação) geridos pelo AlertStore.
 */

const store = useAlertStore()

onMounted(() => store.fetchAlerts())
</script>

<template>
  <div class="animate-fade-in space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text-heading">Alertas</h1>
      <p class="text-sm text-text-muted mt-1">Notificações de anomalias no sistema</p>
    </div>

    <BaseCard>
      <div v-if="store.loading" class="text-center py-12 text-text-muted">
        Carregando alertas...
      </div>

      <div v-else-if="store.alerts.length === 0" class="text-center py-12 text-text-muted">
        Nenhum alerta registrado. ✓
      </div>

      <div v-else class="space-y-3">
        <AlertItem
          v-for="alert in store.alerts"
          :key="alert.id"
          :alert="alert"
          @resolve="store.resolveAlert"
        />
      </div>
    </BaseCard>
  </div>
</template>
