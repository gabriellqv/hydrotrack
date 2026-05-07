<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHydrometerStore } from '@/stores/hydrometer'
import { useAlertStore } from '@/stores/alert'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import ConsumptionChart from '@/components/ConsumptionChart.vue'
import AlertItem from '@/components/AlertItem.vue'
import { ArrowLeft, Download } from 'lucide-vue-next'

/**
 * View de Detalhe do Hidrômetro.
 *
 * Exibe informações completas de um hidrômetro individual, incluindo
 * mini-gráfico das últimas leituras, lista de alertas associados e
 * botão de exportação CSV.
 */

const route = useRoute()
const router = useRouter()
const store = useHydrometerStore()
const alertStore = useAlertStore()

const typeMap: Record<string, string> = {
  residential: 'Residencial',
  commercial: 'Comercial',
  industrial: 'Industrial',
}

onMounted(() => {
  const id = Number(route.params.id)
  if (isNaN(id)) {
    router.push({ name: 'hydrometers' })
    return
  }
  store.fetchHydrometer(id)
})

/** Mapeia leituras para o formato esperado pelo ConsumptionChart */
function readingsToChartData() {
  if (!store.currentHydrometer?.chart_data) return []
  return store.currentHydrometer.chart_data
}

async function handleExport() {
  if (!store.currentHydrometer) return
  await store.exportReadings(store.currentHydrometer.id, store.currentHydrometer.code)
}

/** Opções do seletor de período */
const periodOptions: { days: 7 | 30 | 90; label: string }[] = [
  { days: 7, label: '7d' },
  { days: 30, label: '30d' },
  { days: 90, label: '90d' },
]

/** Título dinâmico do gráfico baseado no período */
const chartTitle = computed(() => {
  const labels: Record<number, string> = {
    7: 'Leituras (últimos 7 dias)',
    30: 'Leituras (últimos 30 dias)',
    90: 'Leituras (últimos 90 dias)',
  }
  return labels[store.detailDays] || 'Leituras'
})

/** Altera o período e recarrega as leituras */
async function changePeriod(days: 7 | 30 | 90) {
  if (!store.currentHydrometer) return
  await store.fetchHydrometer(store.currentHydrometer.id, days)
}

/** Resolve o alerta e recarrega os dados do hidrômetro para atualizar a lista */
async function handleResolveAlert(alertId: number) {
  if (!store.currentHydrometer) return
  await alertStore.resolveAlert(alertId)
  await store.fetchHydrometer(store.currentHydrometer.id, store.detailDays)
}
</script>

<template>
  <div class="animate-fade-in flex flex-col gap-4 pb-6">
    <!-- Header com botão voltar -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-4 shrink-0">
      <div class="flex items-center gap-4 flex-1">
        <button
          @click="router.push({ name: 'hydrometers' })"
          class="rounded-lg p-2 text-text-muted hover:text-text-heading hover:bg-surface-hover transition-colors shrink-0"
        >
          <ArrowLeft class="h-5 w-5" />
        </button>
        <div class="min-w-0">
          <h1 class="text-2xl font-bold text-text-heading truncate">
            {{ store.currentHydrometer?.code ?? 'Carregando...' }}
          </h1>
          <p class="text-sm text-text-muted mt-0.5">Detalhes do hidrômetro</p>
        </div>
      </div>
      <BaseButton
        v-if="store.currentHydrometer"
        variant="secondary"
        @click="handleExport"
        class="w-full sm:w-auto flex justify-center"
      >
        <Download class="h-4 w-4" /> Exportar CSV
      </BaseButton>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="text-center py-12 text-text-muted">Carregando detalhes...</div>

    <template v-else-if="store.currentHydrometer">
      <!-- Grid de informações -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Dados do dispositivo -->
        <BaseCard class="lg:col-span-1">
          <h3 class="text-sm font-bold text-text-heading mb-4 uppercase tracking-wider">
            Informações do Dispositivo
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-text-muted">Código</span>
              <span class="text-text-heading font-mono">{{ store.currentHydrometer.code }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Status</span>
              <StatusBadge :status="store.currentHydrometer.status" />
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Tipo</span>
              <span class="text-text-body">{{
                typeMap[store.currentHydrometer.type] || store.currentHydrometer.type
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Endereço</span>
              <span class="text-text-body text-right max-w-[60%]">{{
                store.currentHydrometer.address
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Bairro</span>
              <span class="text-text-body">{{ store.currentHydrometer.neighborhood }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Coordenadas</span>
              <span class="text-text-muted font-mono text-xs">
                {{ store.currentHydrometer.latitude }},
                {{ store.currentHydrometer.longitude }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Última Leitura</span>
              <span class="text-text-body text-xs">
                {{
                  store.currentHydrometer.last_reading_at
                    ? new Date(store.currentHydrometer.last_reading_at).toLocaleString('pt-BR')
                    : 'Sem leituras'
                }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-text-muted">Cadastrado em</span>
              <span class="text-text-body text-xs">
                {{ new Date(store.currentHydrometer.created_at).toLocaleDateString('pt-BR') }}
              </span>
            </div>
          </div>
        </BaseCard>

        <!-- Gráfico de leituras -->
        <BaseCard class="lg:col-span-2">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h3 class="text-sm font-bold text-text-heading uppercase tracking-wider">
              {{ chartTitle }}
            </h3>
            <div class="flex gap-1 overflow-x-auto pb-1 sm:pb-0 custom-scrollbar">
              <button
                v-for="opt in periodOptions"
                :key="opt.days"
                @click="changePeriod(opt.days)"
                :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200',
                  store.detailDays === opt.days
                    ? 'bg-primary-600 text-white shadow-lg shadow-primary-900/20'
                    : 'bg-surface-card border border-border text-text-muted hover:text-text-heading hover:border-primary-500/30',
                ]"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>
          <div class="h-[250px] relative">
            <ConsumptionChart v-if="readingsToChartData().length" :data="readingsToChartData()" />
            <p v-else class="text-sm text-text-muted text-center py-8 mt-8">
              {{
                store.detailLoading
                  ? 'Carregando dados...'
                  : 'Nenhuma leitura registrada para este período.'
              }}
            </p>
          </div>
        </BaseCard>
      </div>

      <!-- Alertas do hidrômetro -->
      <BaseCard>
        <h3 class="text-sm font-bold text-text-heading mb-4 uppercase tracking-wider">
          Alertas Recentes
        </h3>
        <div
          v-if="store.currentHydrometer.alerts && store.currentHydrometer.alerts.length"
          class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar"
        >
          <AlertItem
            v-for="alert in store.currentHydrometer.alerts"
            :key="alert.id"
            :alert="alert"
            @resolve="handleResolveAlert"
          />
        </div>
        <p v-else class="text-sm text-text-muted text-center py-4">
          Nenhum alerta registrado para este hidrômetro.
        </p>
      </BaseCard>
    </template>
  </div>
</template>
