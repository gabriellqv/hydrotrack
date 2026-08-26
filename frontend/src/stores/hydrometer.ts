import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/services/api'
import type {
  CreateHydrometerPayload,
  Hydrometer,
  PaginatedResponse,
  UpdateHydrometerPayload,
} from '@/types'
import { useToastStore } from '@/stores/toast'

/**
 * Store de hidrômetros - gerencia o estado global de dispositivos.
 *
 * Gerencia o estado de listagem, detalhamento, paginação, criação,
 * atualização, exclusão e exportação de leituras dos hidrômetros.
 */
export const useHydrometerStore = defineStore('hydrometer', () => {
  const hydrometers = ref<Hydrometer[]>([])
  const currentHydrometer = ref<Hydrometer | null>(null)
  const loading = ref(false)
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
  })

  /**
   * Carrega hidrômetros paginados da API, aplicando filtros opcionais.
   *
   * @param page - Número da página.
   * @param filters - Filtros de busca (ex.: neighborhood, status, type).
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
   * Carrega os detalhes de um hidrômetro, incluindo leituras e alertas.
   * Atualiza `detailDays` quando um novo período é fornecido.
   *
   * @param id - Identificador do hidrômetro.
   * @param days - Período das leituras; se omitido, mantém o atual.
   */
  async function fetchHydrometer(id: number, days?: 7 | 30 | 90) {
    if (days !== undefined) {
      detailDays.value = days
      detailLoading.value = true // Apenas o gráfico exibe indicador de carregamento.
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
   * @param payload - Dados do novo hidrômetro.
   * @returns Hidrômetro criado.
   */
  async function createHydrometer(payload: CreateHydrometerPayload): Promise<Hydrometer> {
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
   * @param id - Identificador do hidrômetro.
   * @param payload - Campos a atualizar.
   */
  async function updateHydrometer(id: number, payload: UpdateHydrometerPayload) {
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
   * @param id - Identificador do hidrômetro a remover.
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
   * @param id - Identificador do hidrômetro.
   * @param code - Código do hidrômetro (para nome do arquivo).
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
