import axios, { type AxiosInstance, type AxiosError } from 'axios'
import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
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

  // Interceptor de request: injeta o token de autenticacao
  client.interceptors.request.use((config) => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  })

  // Interceptor de response: trata erros de rede e 401 (token expirado/invalido)
  client.interceptors.response.use(
    (response) => response,
    createResponseErrorHandler(() => ({
      authStore: useAuthStore(),
      router,
      toastStore: useToastStore(),
    })),
  )

  return client
}

export function createResponseErrorHandler(
  resolveDependencies: () => {
    authStore: ReturnType<typeof useAuthStore>
    router: Router
    toastStore: ReturnType<typeof useToastStore>
  },
) {
  return (error: AxiosError<{ message: string; errors?: Record<string, string[]> }>) => {
    const { authStore, router, toastStore } = resolveDependencies()

    if (error.code === 'ERR_NETWORK' || !error.response) {
      const message = 'Servidor indisponível. Verifique sua conexão.'
      toastStore.error(message)
      throw new ApiError(message, 0)
    }

    if (error.response.status === 401 && error.config?.url !== '/auth/logout') {
      authStore.logout()
      router.push({
        name: 'login',
        query: { redirect: router.currentRoute.value.fullPath },
      })
    }

    const status = error.response.status
    const errors = error.response.data?.errors

    /** Mensagens amigáveis por status — nunca expõe detalhes técnicos ao usuário */
    const friendlyMessages: Record<number, string> = {
      400: 'Requisição inválida. Verifique os dados informados.',
      401: 'Credenciais inválidas. Verifique seu e-mail e senha.',
      403: 'Você não tem permissão para realizar esta ação.',
      404: 'Recurso não encontrado.',
      422: error.response.data?.message || 'Erro de validação. Verifique os campos.',
      429: 'Muitas tentativas. Aguarde um momento e tente novamente.',
      500: 'Erro no servidor. Tente novamente mais tarde.',
      502: 'Erro no servidor. Tente novamente mais tarde.',
      503: 'Erro no servidor. Tente novamente mais tarde.',
      504: 'Erro no servidor. Tente novamente mais tarde.',
    }

    const message =
      friendlyMessages[status] ||
      error.response.data?.message ||
      'Erro inesperado na comunicação com o servidor. Tente novamente.'

    if (status >= 500) {
      toastStore.error(message)
    }

    throw new ApiError(message, status, errors)
  }
}

/** Instância global do cliente API */
export const api = createApiClient()
