import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useHydrometerStore } from '../hydrometer'
import { api } from '@/services/api'

/**
 * Testes unitários da store de hidrômetros.
 *
 * Validam o gerenciamento de estado (carregamento, paginação)
 * e a comunicação com a API via mocks do Axios.
 */

vi.mock('@/services/api')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('useHydrometerStore', () => {
  it('inicia com estado vazio', () => {
    const store = useHydrometerStore()

    expect(store.hydrometers).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.pagination.total).toBe(0)
  })

  it('carrega hidrômetros da API com paginação', async () => {
    const mockResponse = {
      data: {
        data: [
          { id: 1, code: 'HYD-001', status: 'online' },
          { id: 2, code: 'HYD-002', status: 'offline' },
        ],
        meta: { current_page: 1, last_page: 3, per_page: 15, total: 45 },
      },
    }
    vi.mocked(api.get).mockResolvedValue(mockResponse)

    const store = useHydrometerStore()
    await store.fetchHydrometers()

    expect(store.hydrometers).toHaveLength(2)
    expect(store.pagination.total).toBe(45)
    expect(store.loading).toBe(false)
  })

  it('define loading como true durante o fetch', async () => {
    vi.mocked(api.get).mockImplementation(
      () =>
        new Promise((resolve) =>
          setTimeout(
            () =>
              resolve({
                data: { data: [], meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 } },
              }),
            100,
          ),
        ),
    )

    const store = useHydrometerStore()
    const promise = store.fetchHydrometers()

    expect(store.loading).toBe(true)
    await promise
  })
})
