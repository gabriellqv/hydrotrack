import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAlertStore } from '../alert'
import { api } from '@/services/api'

/**
 * Testes unitários da store de alertas.
 *
 * Validam a listagem paginada e a busca das métricas agregadas,
 * garantindo que os KPIs não sejam derivados da página atual.
 */

vi.mock('@/services/api')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('useAlertStore', () => {
  it('inicia com estado vazio', () => {
    const store = useAlertStore()

    expect(store.alerts).toEqual([])
    expect(store.stats).toBeNull()
    expect(store.loading).toBe(false)
  })

  it('carrega alertas paginados da API', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: [
          { id: 1, type: 'offline', message: 'Sem sinal', resolved: false },
          { id: 2, type: 'zero_reading', message: 'Leitura zero', resolved: true },
        ],
      },
    })

    const store = useAlertStore()
    await store.fetchAlerts()

    expect(store.alerts).toHaveLength(2)
    expect(store.loading).toBe(false)
  })

  it('busca as métricas agregadas de todo o histórico', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: { total: 40, resolved: 10, pending: 30, resolution_rate: 25 },
    })

    const store = useAlertStore()
    await store.fetchStats()

    expect(api.get).toHaveBeenCalledWith('/alerts/stats')
    expect(store.stats).toEqual({ total: 40, resolved: 10, pending: 30, resolution_rate: 25 })
  })

  it('mantém stats como null ao ocorrer erro na busca de métricas', async () => {
    vi.mocked(api.get).mockRejectedValue(new Error('Endpoint indisponível'))

    const store = useAlertStore()
    await store.fetchStats()

    expect(store.stats).toBeNull()
  })
})
