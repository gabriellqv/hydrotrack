import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Alert } from '@/types'

/**
 * Store de alertas — gerencia listagem e resolução de alertas.
 */
export const useAlertStore = defineStore('alert', () => {
  const alerts = ref<Alert[]>([])
  const loading = ref(false)

  async function fetchAlerts() {
    loading.value = true
    try {
      const { data } = await api.get<{ data: Alert[] }>('/alerts')
      alerts.value = data.data
    } finally {
      loading.value = false
    }
  }

  async function resolveAlert(id: number) {
    await api.patch(`/alerts/${id}/resolve`)
    await fetchAlerts()
  }

  return { alerts, loading, fetchAlerts, resolveAlert }
})
