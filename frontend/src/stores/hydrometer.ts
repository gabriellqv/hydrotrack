import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type { Hydrometer, PaginatedResponse } from '@/types'
import { useToastStore } from '@/stores/toast'

/**
 * Store de hidrômetros — gerencia o estado global de dispositivos.
 *
 * Segue o padrão de "setup store" do Pinia (Composition API style),
 * separando estado (refs), ações (funções) e getters (computed).
 */
export const useHydrometerStore = defineStore('hydrometer', () => {
  /** Lista de hidrômetros carregados */
  const hydrometers = ref<Hydrometer[]>([])

  /** Hidrômetro carregado individualmente (página de detalhe) */
  const currentHydrometer = ref<Hydrometer | null>(null)

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

  /** Período selecionado para leituras na página de detalhe */
  const detailDays = ref<7 | 30 | 90>(30)

  /** Indicador de carregamento para refresh das leituras do detalhe */
  const detailLoading = ref(false)

  /**
   * Busca os detalhes de um hidrômetro individual com leituras e alertas.
   *
   * @param {number} id - ID do hidrômetro
   * @param {7 | 30 | 90} days - Período das leituras em dias
   */
  async function fetchHydrometer(id: number, days?: 7 | 30 | 90) {
    if (days !== undefined) {
      detailDays.value = days
      detailLoading.value = true // Apenas o gráfico ficará com "loading"
    } else {
      loading.value = true // Primeira vez carregando a tela inteira
    }

    try {
      const { data } = await api.get<{ data: Hydrometer }>(
        `/hydrometers/${id}?days=${detailDays.value}`,
      )
      currentHydrometer.value = data.data
    } finally {
      loading.value = false
      detailLoading.value = false
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
    const toast = useToastStore()
    try {
      const { data } = await api.post<{ data: Hydrometer }>('/hydrometers', payload)
      toast.success('Hidrômetro registrado com sucesso.')
      await fetchHydrometers(pagination.value.currentPage)
      return data.data
    } catch (error) {
      toast.error('Erro ao cadastrar hidrômetro. Verifique os dados.')
      throw error
    }
  }

  /**
   * Atualiza um hidrômetro existente.
   *
   * @param {number} id - ID do hidrômetro
   * @param {Partial<Hydrometer>} payload - Campos a atualizar
   */
  async function updateHydrometer(id: number, payload: Partial<Hydrometer>) {
    const toast = useToastStore()
    try {
      await api.put(`/hydrometers/${id}`, payload)
      toast.success('Hidrômetro atualizado com sucesso.')
      await fetchHydrometers(pagination.value.currentPage)
    } catch (error) {
      toast.error('Falha ao atualizar informações do hidrômetro.')
      throw error
    }
  }

  /**
   * Remove um hidrômetro do sistema.
   *
   * @param {number} id - ID do hidrômetro a remover
   */
  async function deleteHydrometer(id: number) {
    const toast = useToastStore()
    try {
      await api.delete(`/hydrometers/${id}`)
      toast.success('Hidrômetro removido com sucesso.')
      await fetchHydrometers(pagination.value.currentPage)
    } catch {
      toast.error('Não foi possível remover o hidrômetro.')
    }
  }

  /**
   * Exporta as leituras do hidrômetro em formato CSV via download.
   *
   * @param {number} id - ID do hidrômetro
   * @param {string} code - Código do hidrômetro (para nome do arquivo)
   */
  async function exportReadings(id: number, code: string) {
    const toast = useToastStore()
    try {
      const response = await api.get(`/hydrometers/${id}/readings/export`, {
        responseType: 'blob',
      })
      const url = window.URL.createObjectURL(new Blob([response.data as BlobPart]))
      const link = document.createElement('a')
      link.href = url
      link.download = `${code}_leituras.csv`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)

      toast.success('Download do arquivo CSV iniciado.')
    } catch {
      toast.error('Erro ao gerar o arquivo de exportação.')
    }
  }

  return {
    hydrometers,
    currentHydrometer,
    loading,
    pagination,
    detailDays,
    detailLoading,
    fetchHydrometers,
    fetchHydrometer,
    createHydrometer,
    updateHydrometer,
    deleteHydrometer,
    exportReadings,
  }
})
