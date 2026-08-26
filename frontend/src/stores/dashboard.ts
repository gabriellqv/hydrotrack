import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import type { DashboardSummary, ConsumptionPoint, Hydrometer, Alert } from '@/types'

/**
 * Store do dashboard - gerencia dados de resumo, gráfico, mapa e alertas.
 */
export const useDashboardStore = defineStore('dashboard', () => {
  const summary = ref<DashboardSummary | null>(null)
  const consumption = ref<ConsumptionPoint[]>([])
  const mapHydrometers = ref<Hydrometer[]>([])
  const recentAlerts = ref<Alert[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /** Período selecionado para o gráfico de consumo (em dias) */
  const selectedDays = ref<7 | 30 | 90>(30)

  /**
   * Limpa o estado de erro da store.
   */
  function clearError() {
    error.value = null
  }

  /**
   * Normaliza erros de requisição, ignorando cancelamentos controlados por AbortSignal.
   * Em outros casos, armazena a mensagem e exibe um toast de erro.
   */
  function handleError(message: string, err: unknown) {
    const toast = useToastStore()
    if (isCancelled(err)) {
      return
    }
    error.value = message
    toast.error(message)
  }

  /**
   * Carrega o resumo estatístico do dashboard.
   *
   * @param signal - Sinal opcional para abortar a requisição.
   */
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

  /**
   * Carrega os dados de consumo para o período informado.
   * Atualiza `selectedDays` quando um novo período é fornecido.
   *
   * @param days - Período em dias; se omitido, usa `selectedDays`.
   * @param signal - Sinal opcional para abortar a requisição.
   */
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

  /**
   * Carrega os hidrômetros para exibição no mapa.
   *
   * @param signal - Sinal opcional para abortar a requisição.
   */
  async function fetchMap(signal?: AbortSignal) {
    try {
      const { data } = await api.get<Hydrometer[]>('/dashboard/map', { signal })
      mapHydrometers.value = data
    } catch (err) {
      handleError('Erro ao carregar dados do mapa.', err)
      throw err
    }
  }

  /**
   * Carrega os alertas mais recentes, limitando aos 5 primeiros registros.
   *
   * @param signal - Sinal opcional para abortar a requisição.
   */
  async function fetchAlerts(signal?: AbortSignal) {
    try {
      const { data } = await api.get<{ data: Alert[] }>('/alerts', { signal })
      // A API pode retornar a lista direta ou embrulhada em `data`. Limita aos 5 mais recentes.
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

/**
 * Verifica se o erro é resultante de um aborto de requisição.
 */
function isCancelled(err: unknown): boolean {
  return err instanceof DOMException && err.name === 'AbortError'
}
