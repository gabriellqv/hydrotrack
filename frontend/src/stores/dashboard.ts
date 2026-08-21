import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import type { DashboardSummary, ConsumptionPoint, Hydrometer, Alert } from '@/types'

/**
 * Store do dashboard — gerencia dados de resumo, grafico, mapa e alertas.
 */
export const useDashboardStore = defineStore('dashboard', () => {
  const summary = ref<DashboardSummary | null>(null)
  const consumption = ref<ConsumptionPoint[]>([])
  const mapHydrometers = ref<Hydrometer[]>([])
  const recentAlerts = ref<Alert[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /** Periodo selecionado para o grafico de consumo (em dias) */
  const selectedDays = ref<7 | 30 | 90>(30)

  function clearError() {
    error.value = null
  }

  function handleError(message: string, err: unknown) {
    const toast = useToastStore()
    if (isCancelled(err)) {
      return
    }
    error.value = message
    toast.error(message)
  }

  async function fetchSummary(signal?: AbortSignal) {
    loading.value = true
    clearError()
    try {
      const { data } = await api.get<DashboardSummary>('/dashboard/summary', { signal })
      summary.value = data
    } catch (err) {
      handleError('Erro ao carregar resumo do dashboard.', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchConsumption(days?: 7 | 30 | 90, signal?: AbortSignal) {
    if (days !== undefined) {
      selectedDays.value = days
    }
    try {
      const { data } = await api.get<ConsumptionPoint[]>(
        `/dashboard/consumption?days=${selectedDays.value}`,
        { signal },
      )
      consumption.value = data
    } catch (err) {
      handleError('Erro ao carregar dados de consumo.', err)
      throw err
    }
  }

  async function fetchMap(signal?: AbortSignal) {
    try {
      const { data } = await api.get<Hydrometer[]>('/dashboard/map', { signal })
      mapHydrometers.value = data
    } catch (err) {
      handleError('Erro ao carregar dados do mapa.', err)
      throw err
    }
  }

  async function fetchAlerts(signal?: AbortSignal) {
    try {
      const { data } = await api.get<{ data: Alert[] }>('/alerts', { signal })
      recentAlerts.value = (data.data || data).slice(0, 5)
    } catch (err) {
      handleError('Erro ao carregar alertas recentes.', err)
      throw err
    }
  }

  return {
    summary,
    consumption,
    mapHydrometers,
    recentAlerts,
    loading,
    error,
    selectedDays,
    fetchSummary,
    fetchConsumption,
    fetchMap,
    fetchAlerts,
  }
})

function isCancelled(err: unknown): boolean {
  return err instanceof DOMException && err.name === 'AbortError'
}
