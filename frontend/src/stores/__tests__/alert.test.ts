import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAlertStore } from '../alert'
import { api } from '@/services/api'
import type { Alert } from '@/types'

/**
 * Testes unitarios da store de alertas.
 *
 * Validam listagem com filtros e resolucao de alertas.
 */

vi.mock('@/services/api')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('useAlertStore', () => {
  it('inicia com lista vazia e filtros padrao', () => {
    const store = useAlertStore()

    expect(store.alerts).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.filters).toEqual({ type: '', resolved: '' })
  })

  it('carrega alertas da API sem filtros', async () => {
    const mockAlerts: Alert[] = [
      {
        id: 1,
        hydrometer_id: 1,
        type: 'offline',
        message: 'Offline',
        resolved: false,
        resolved_at: null,
        created_at: '',
      },
    ]
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockAlerts } })

    const store = useAlertStore()
    await store.fetchAlerts()

    expect(store.alerts).toEqual(mockAlerts)
    expect(store.loading).toBe(false)
    expect(api.get).toHaveBeenCalledWith('/alerts')
  })

  it('aplica filtros de tipo e resolucao na URL', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { data: [] } })

    const store = useAlertStore()
    store.filters = { type: 'offline', resolved: 'false' }
    await store.fetchAlerts()

    expect(api.get).toHaveBeenCalledWith('/alerts?type=offline&resolved=false')
  })

  it('resolve um alerta e recarrega a lista', async () => {
    vi.mocked(api.patch).mockResolvedValue({ data: {} })
    vi.mocked(api.get).mockResolvedValue({ data: { data: [] } })

    const store = useAlertStore()
    await store.resolveAlert(1)

    expect(api.patch).toHaveBeenCalledWith('/alerts/1/resolve')
    expect(api.get).toHaveBeenCalledWith('/alerts')
  })
})
