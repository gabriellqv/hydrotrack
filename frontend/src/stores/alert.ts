import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Alert } from '@/types'
import { useToastStore } from '@/stores/toast'

/**
 * Store de alertas — gerencia listagem, filtragem e resolução de alertas.
 */
export const useAlertStore = defineStore('alert', () => {
  const alerts = ref<Alert[]>([])
  const loading = ref(false)

  /** Filtros ativos para a listagem de alertas */
  const filters = ref<{ type: string; resolved: string }>({
    type: '',
    resolved: '',
  })

  /** Garante que apenas a resposta mais recente seja aplicada (evita race condition) */
  let fetchSeq = 0

  async function fetchAlerts() {
    const seq = ++fetchSeq
    if (!alerts.value.length) loading.value = true
    try {
      const params = new URLSearchParams()
      if (filters.value.type) params.set('type', filters.value.type)
      if (filters.value.resolved) params.set('resolved', filters.value.resolved)
      const query = params.toString()
      const { data } = await api.get<{ data: Alert[] }>(`/alerts${query ? `?${query}` : ''}`)
      if (seq !== fetchSeq) return
      alerts.value = data.data
    } finally {
      if (seq === fetchSeq) loading.value = false
    }
  }

  async function resolveAlert(id: number) {
    const toast = useToastStore()
    try {
      await api.patch(`/alerts/${id}/resolve`)
      toast.success('Alerta resolvido e arquivado com sucesso.')
      await fetchAlerts()
    } catch {
      toast.error('Erro ao tentar resolver o alerta.')
    }
  }

  return { alerts, loading, filters, fetchAlerts, resolveAlert }
})
