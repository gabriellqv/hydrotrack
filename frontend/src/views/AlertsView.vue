<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useAlertStore } from '@/stores/alert'
import BaseCard from '@/components/ui/BaseCard.vue'
import AlertItem from '@/components/AlertItem.vue'

/**
 * View de Alertas.
 *
 * Exibe a listagem de todos os alertas do sistema (anomalias de telemetria
 * e falhas de comunicação) geridos pelo AlertStore. Inclui filtros por
 * tipo de alerta e status de resolução.
 */

const store = useAlertStore()

/** Contadores calculados a partir dos alertas carregados */
const pendingCount = computed(() => store.alerts.filter((a) => !a.resolved).length)
const resolvedCount = computed(() => store.alerts.filter((a) => a.resolved).length)

/** Altera o filtro de tipo e recarrega os alertas */
function filterByType(type: string) {
  store.filters.type = store.filters.type === type ? '' : type
  store.fetchAlerts()
}

/** Altera o filtro de status e recarrega os alertas */
function filterByResolved(resolved: string) {
  store.filters.resolved = store.filters.resolved === resolved ? '' : resolved
  store.fetchAlerts()
}

onMounted(() => store.fetchAlerts())
</script>

<template>
  <div class="animate-fade-in view-scroll-layout">
    <div class="shrink-0">
      <h1 class="text-2xl font-bold text-text-heading">Alertas</h1>
      <p class="text-sm text-text-muted mt-1">
        {{ store.alerts.length }} alertas carregados
        <span v-if="pendingCount > 0" class="text-amber-400 font-medium">
          ({{ pendingCount }} pendentes)
        </span>
        <span v-if="resolvedCount > 0" class="text-green-400">
          ({{ resolvedCount }} resolvidos)
        </span>
      </p>
    </div>

    <!-- Filtros -->
    <BaseCard compact class="shrink-0">
      <div class="flex flex-wrap gap-2">
        <!-- Filtros por tipo -->
        <span class="text-xs text-text-muted self-center mr-1">Tipo:</span>
        <button
          v-for="opt in [
            { value: 'high_consumption', label: 'Consumo Alto' },
            { value: 'zero_reading', label: 'Leitura Zero' },
            { value: 'offline', label: 'Sem Comunicação' },
          ]"
          :key="opt.value"
          @click="filterByType(opt.value)"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200',
            store.filters.type === opt.value
              ? 'bg-primary-600 text-white shadow-lg shadow-primary-900/20'
              : 'bg-surface-card border border-border text-text-muted hover:text-text-heading hover:border-primary-500/30',
          ]"
        >
          {{ opt.label }}
        </button>

        <div class="w-px bg-border mx-1 self-stretch"></div>

        <!-- Filtros por status -->
        <span class="text-xs text-text-muted self-center mr-1">Status:</span>
        <button
          @click="filterByResolved('false')"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200',
            store.filters.resolved === 'false'
              ? 'bg-amber-600/20 border border-amber-500/50 text-amber-400'
              : 'bg-surface-card border border-border text-text-muted hover:text-amber-400 hover:border-amber-500/30',
          ]"
        >
          Pendentes
        </button>
        <button
          @click="filterByResolved('true')"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200',
            store.filters.resolved === 'true'
              ? 'bg-green-600/20 border border-green-500/50 text-green-400'
              : 'bg-surface-card border border-border text-text-muted hover:text-green-400 hover:border-green-500/30',
          ]"
        >
          Resolvidos
        </button>
      </div>
    </BaseCard>

    <BaseCard class="view-scroll-card">
      <div v-if="store.loading" class="text-center py-12 text-text-muted">
        Carregando alertas...
      </div>

      <div v-else-if="store.alerts.length === 0" class="text-center py-12 text-text-muted">
        Nenhum alerta encontrado para os filtros selecionados.
      </div>

      <div v-else class="view-scroll-content space-y-3">
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
