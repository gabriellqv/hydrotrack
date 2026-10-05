import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Alert, AlertStats } from '@/types'
import { useToastStore } from '@/stores/toast'

/**
 * Store de alertas — gerencia listagem, filtragem e resolução de alertas.
 */
export const useAlertStore = defineStore('alert', () => {
  const alerts = ref<Alert[]>([])
  const loading = ref(false)

  /** Métricas agregadas de todo o histórico (para os KPIs da Central de Alertas) */
  const stats = ref<AlertStats | null>(null)

  /** Filtros ativos para a listagem de alertas */
  const filters = ref<{ type: string; resolved: string }>({
    type: '',
    resolved: '',
  })

  async function fetchAlerts() {
    if (!alerts.value.length) loading.value = true
    try {
      const params = new URLSearchParams()
      if (filters.value.type) params.set('type', filters.value.type)
      if (filters.value.resolved) params.set('resolved', filters.value.resolved)
      const query = params.toString()
      const { data } = await api.get<{ data: Alert[] }>(`/alerts${query ? `?${query}` : ''}`)
      alerts.value = data.data
    } finally {
      loading.value = false
    }
  }

  /** Busca as métricas agregadas de alertas (totais e taxa de resolução) */
  async function fetchStats() {
    const { data } = await api.get<AlertStats>('/alerts/stats')
    stats.value = data
  }

  async function resolveAlert(id: number) {
    const toast = useToastStore()
    try {
      await api.patch(`/alerts/${id}/resolve`)
      toast.success('Alerta resolvido e arquivado com sucesso.')
      await Promise.all([fetchAlerts(), fetchStats()])
    } catch {
      toast.error('Erro ao tentar resolver o alerta.')
    }
  }

  return { alerts, stats, loading, filters, fetchAlerts, fetchStats, resolveAlert }
})
