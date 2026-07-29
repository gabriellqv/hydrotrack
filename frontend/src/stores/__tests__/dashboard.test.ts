import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboardStore } from '../dashboard'
import { api } from '@/services/api'
import type { DashboardSummary, ConsumptionPoint, Hydrometer, Alert } from '@/types'

/**
 * Testes unitarios da store do dashboard.
 *
 * Validam carregamento de resumo, consumo, mapa e alertas recentes.
 */

vi.mock('@/services/api')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('useDashboardStore', () => {
  it('inicia com valores padrao', () => {
    const store = useDashboardStore()

    expect(store.summary).toBeNull()
    expect(store.consumption).toEqual([])
    expect(store.mapHydrometers).toEqual([])
    expect(store.recentAlerts).toEqual([])
    expect(store.selectedDays).toBe(30)
  })

  it('carrega resumo do dashboard', async () => {
    const mockSummary: DashboardSummary = {
      total_hydrometers: 10,
      online: 8,
      offline: 1,
      alert: 1,
      total_readings_today: 50,
      pending_alerts: 2,
    }
    vi.mocked(api.get).mockResolvedValue({ data: mockSummary })

    const store = useDashboardStore()
    await store.fetchSummary()

    expect(store.summary).toEqual(mockSummary)
    expect(store.loading).toBe(false)
  })

  it('carrega dados de consumo mantendo periodo padrao', async () => {
    const mockConsumption: ConsumptionPoint[] = [{ date: '2026-07-01', total_m3: 100 }]
    vi.mocked(api.get).mockResolvedValue({ data: mockConsumption })

    const store = useDashboardStore()
    await store.fetchConsumption()

    expect(store.consumption).toEqual(mockConsumption)
    expect(store.selectedDays).toBe(30)
    expect(api.get).toHaveBeenCalledWith('/dashboard/consumption?days=30')
  })

  it('altera periodo ao carregar consumo', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: [] })

    const store = useDashboardStore()
    await store.fetchConsumption(90)

    expect(store.selectedDays).toBe(90)
    expect(api.get).toHaveBeenCalledWith('/dashboard/consumption?days=90')
  })

  it('carrega hidrometros para o mapa', async () => {
    const mockHydrometers: Hydrometer[] = [
      { id: 1, code: 'HYD-001', status: 'online', latitude: 0, longitude: 0 } as Hydrometer,
    ]
    vi.mocked(api.get).mockResolvedValue({ data: mockHydrometers })

    const store = useDashboardStore()
    await store.fetchMap()

    expect(store.mapHydrometers).toEqual(mockHydrometers)
  })

  it('carrega ate 5 alertas recentes', async () => {
    const mockAlerts: Alert[] = Array.from({ length: 7 }, (_, i) => ({
      id: i + 1,
      hydrometer_id: 1,
      type: 'offline',
      message: `Alert ${i + 1}`,
      resolved: false,
      resolved_at: null,
      created_at: '',
    }))
    vi.mocked(api.get).mockResolvedValue({ data: { data: mockAlerts } })

    const store = useDashboardStore()
    await store.fetchAlerts()

    expect(store.recentAlerts).toHaveLength(5)
    expect(store.recentAlerts[0]?.id).toBe(1)
  })
})
