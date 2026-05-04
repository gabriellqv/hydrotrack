import axios, { type AxiosInstance, type AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

/**
 * Erro customizado da API com tipagem forte.
 *
 * Encapsula erros HTTP da API Laravel, expondo a mensagem de erro
 * e o status code de forma consistente para os stores e componentes.
 *
 * @class ApiError
 * @extends Error
 */
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

/**
 * Cria e configura a instância Axios centralizada.
 *
 * Intercepta todas as requests para injetar o Bearer token
 * e intercepta responses 401 para redirecionar ao login.
 *
 * @returns {AxiosInstance} Instância configurada do Axios
 */
function createApiClient(): AxiosInstance {
  const client = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  })

  // Interceptor de request: injeta o token de autenticação
  client.interceptors.request.use((config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  })

  // Interceptor de response: trata 401 (token expirado/inválido)
  client.interceptors.response.use(
    (response) => response,
    (error: AxiosError<{ message: string; errors?: Record<string, string[]> }>) => {
      if (error.response?.status === 401) {
        const authStore = useAuthStore()
        authStore.logout()
        router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
      }

      const message =
        error.response?.data?.message || 'Erro inesperado na comunicação com o servidor.'
      const status = error.response?.status || 500
      const errors = error.response?.data?.errors

      throw new ApiError(message, status, errors)
    },
  )

  return client
}

/** Instância global do cliente API */
export const api = createApiClient()
