import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { DashboardSummary, ConsumptionPoint, Hydrometer } from '@/types'

/**
 * Store do dashboard — gerencia dados de resumo, gráfico e mapa.
 */
export const useDashboardStore = defineStore('dashboard', () => {
  const summary = ref<DashboardSummary | null>(null)
  const consumption = ref<ConsumptionPoint[]>([])
  const mapHydrometers = ref<Hydrometer[]>([])
  const loading = ref(false)

  async function fetchSummary() {
    loading.value = true
    try {
      const { data } = await api.get<DashboardSummary>('/dashboard/summary')
      summary.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchConsumption() {
    const { data } = await api.get<ConsumptionPoint[]>('/dashboard/consumption')
    consumption.value = data
  }

  async function fetchMap() {
    const { data } = await api.get<Hydrometer[]>('/dashboard/map')
    mapHydrometers.value = data
  }

  return { summary, consumption, mapHydrometers, loading, fetchSummary, fetchConsumption, fetchMap }
})
