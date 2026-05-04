import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Hydrometer, PaginatedResponse } from '@/types'

/**
 * Store de hidrômetros — gerencia o estado global de dispositivos.
 *
 * Segue o padrão de "setup store" do Pinia (Composition API style),
 * separando estado (refs), ações (funções) e getters (computed).
 */
export const useHydrometerStore = defineStore('hydrometer', () => {
  /** Lista de hidrômetros carregados */
  const hydrometers = ref<Hydrometer[]>([])

  /** Indicador de carregamento */
  const loading = ref(false)

  /** Metadados de paginação */
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
  })

  /**
   * Busca hidrômetros da API com paginação e filtros.
   *
   * @param {number} page - Número da página (default: 1)
   * @param {Record<string, string>} filters - Filtros opcionais (neighborhood, status, type)
   */
  async function fetchHydrometers(page = 1, filters: Record<string, string> = {}) {
    loading.value = true
    try {
      const params = new URLSearchParams({ page: String(page), ...filters })
      const { data } = await api.get<PaginatedResponse<Hydrometer>>(`/hydrometers?${params}`)

      hydrometers.value = data.data
      pagination.value = {
        currentPage: data.meta.current_page,
        lastPage: data.meta.last_page,
        total: data.meta.total,
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Cria um novo hidrômetro via API.
   *
   * @param {Omit<Hydrometer, 'id' | 'created_at' | 'status' | 'last_reading_at'>} payload
   * @returns {Promise<Hydrometer>} O hidrômetro criado
   */
  async function createHydrometer(
    payload: Omit<Hydrometer, 'id' | 'created_at' | 'status' | 'last_reading_at'>,
  ): Promise<Hydrometer> {
    const { data } = await api.post<{ data: Hydrometer }>('/hydrometers', payload)
    await fetchHydrometers(pagination.value.currentPage)
    return data.data
  }

  /**
   * Atualiza um hidrômetro existente.
   *
   * @param {number} id - ID do hidrômetro
   * @param {Partial<Hydrometer>} payload - Campos a atualizar
   */
  async function updateHydrometer(id: number, payload: Partial<Hydrometer>) {
    await api.put(`/hydrometers/${id}`, payload)
    await fetchHydrometers(pagination.value.currentPage)
  }

  /**
   * Remove um hidrômetro do sistema.
   *
   * @param {number} id - ID do hidrômetro a remover
   */
  async function deleteHydrometer(id: number) {
    await api.delete(`/hydrometers/${id}`)
    await fetchHydrometers(pagination.value.currentPage)
  }

  return {
    hydrometers,
    loading,
    pagination,
    fetchHydrometers,
    createHydrometer,
    updateHydrometer,
    deleteHydrometer,
  }
})
