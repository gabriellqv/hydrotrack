import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import type { Alert } from '@/types'

/**
 * Store de alertas — gerencia listagem, filtragem e resolucao de alertas.
 */
export const useAlertStore = defineStore('alert', () => {
  const alerts = ref<Alert[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /** Filtros ativos para a listagem de alertas */
  const filters = ref<{ type: string; resolved: string }>({
    type: '',
    resolved: '',
  })

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

  async function fetchAlerts(signal?: AbortSignal) {
    loading.value = true
    clearError()
    try {
      const params = new URLSearchParams()
      if (filters.value.type) params.set('type', filters.value.type)
      if (filters.value.resolved) params.set('resolved', filters.value.resolved)
      const query = params.toString()
      const { data } = await api.get<{ data: Alert[] }>(`/alerts${query ? `?${query}` : ''}`, {
        signal,
      })
      alerts.value = data.data
    } catch (err) {
      handleError('Erro ao carregar alertas.', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function resolveAlert(id: number) {
    const toast = useToastStore()
    clearError()
    try {
      await api.patch(`/alerts/${id}/resolve`)
      toast.success('Alerta resolvido e arquivado com sucesso.')
      await fetchAlerts()
    } catch (err) {
      handleError('Erro ao tentar resolver o alerta.', err)
      throw err
    }
  }

  return { alerts, loading, error, filters, fetchAlerts, resolveAlert }
})

function isCancelled(err: unknown): boolean {
  return err instanceof DOMException && err.name === 'AbortError'
}
