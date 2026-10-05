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

  /**
   * Contadores de requisição por recurso. Garantem que apenas a resposta
   * mais recente seja aplicada, evitando condições de corrida quando o
   * polling e o refresh manual (ou trocas rápidas de período) se sobrepõem.
   */
  const requestSeq = { summary: 0, consumption: 0, map: 0, alerts: 0 }

  async function fetchSummary() {
    const seq = ++requestSeq.summary
    if (!summary.value) loading.value = true
    try {
      const { data } = await api.get<DashboardSummary>('/dashboard/summary')
      if (seq !== requestSeq.summary) return
      summary.value = data
    } finally {
      if (seq === requestSeq.summary) loading.value = false
    }
  }

  async function fetchConsumption(days?: 7 | 30 | 90) {
    if (days !== undefined) {
      selectedDays.value = days
    }
    const requestedDays = selectedDays.value
    const seq = ++requestSeq.consumption
    const { data } = await api.get<ConsumptionPoint[]>(
      `/dashboard/consumption?days=${requestedDays}`,
    )
    if (seq !== requestSeq.consumption) return
    consumption.value = data
  }

  async function fetchMap() {
    const seq = ++requestSeq.map
    const { data } = await api.get<Hydrometer[]>('/dashboard/map')
    if (seq !== requestSeq.map) return
    mapHydrometers.value = data
  }

  async function fetchAlerts() {
    const seq = ++requestSeq.alerts
    const { data } = await api.get<{ data: Alert[] }>('/alerts')
    if (seq !== requestSeq.alerts) return
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
