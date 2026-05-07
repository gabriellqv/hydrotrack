import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { DashboardSummary, ConsumptionPoint, Hydrometer, Alert } from '@/types'

/**
 * Store do dashboard — gerencia dados de resumo, gráfico, mapa e alertas.
 */
export const useDashboardStore = defineStore('dashboard', () => {
  const summary = ref<DashboardSummary | null>(null)
  const consumption = ref<ConsumptionPoint[]>([])
  const mapHydrometers = ref<Hydrometer[]>([])
  const recentAlerts = ref<Alert[]>([])
  const loading = ref(false)

  /** Período selecionado para o gráfico de consumo (em dias) */
  const selectedDays = ref<7 | 30 | 90>(30)

  async function fetchSummary() {
    loading.value = true
    try {
      const { data } = await api.get<DashboardSummary>('/dashboard/summary')
      summary.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchConsumption(days?: 7 | 30 | 90) {
    if (days !== undefined) {
      selectedDays.value = days
    }
    const { data } = await api.get<ConsumptionPoint[]>(
      `/dashboard/consumption?days=${selectedDays.value}`,
    )
    consumption.value = data
  }

  async function fetchMap() {
    const { data } = await api.get<Hydrometer[]>('/dashboard/map')
    mapHydrometers.value = data
  }

  async function fetchAlerts() {
    const { data } = await api.get<{ data: Alert[] }>('/alerts')
    recentAlerts.value = (data.data || data).slice(0, 5)
  }

  return {
    summary,
    consumption,
    mapHydrometers,
    recentAlerts,
    loading,
    selectedDays,
    fetchSummary,
    fetchConsumption,
    fetchMap,
    fetchAlerts,
  }
})
