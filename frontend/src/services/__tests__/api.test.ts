import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createWebHistory, type Router } from 'vue-router'
import type { AxiosError, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { ApiError, createResponseErrorHandler } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

/**
 * Testes do handler de erro do interceptor de response da API.
 *
 * Validam o redirecionamento automatico em 401 e as mensagens amigaveis
 * por status code, garantindo que o usuario nao veja detalhes tecnicos.
 */
describe('createResponseErrorHandler', () => {
  let router: Router
  let authStore: ReturnType<typeof useAuthStore>
  let handler: ReturnType<typeof createResponseErrorHandler>
  let routerPushSpy: ReturnType<typeof vi.spyOn>

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
    authStore.token = 'fake-token'

    vi.spyOn(authStore, 'logout').mockImplementation(async () => {
      authStore.token = null
      authStore.user = null
      localStorage.removeItem('auth_token')
    })

    router = createRouter({
      history: createWebHistory(),
      routes: [
        { path: '/', name: 'home', component: { template: '<div />' } },
        { path: '/login', name: 'login', component: { template: '<div />' } },
        { path: '/dashboard', name: 'dashboard', component: { template: '<div />' } },
      ],
    })

    router.push('/dashboard')
    routerPushSpy = vi.spyOn(router, 'push').mockResolvedValue(undefined)

    handler = createResponseErrorHandler(() => ({ authStore, router }))
  })

  afterEach(() => {
    vi.restoreAllMocks()
    localStorage.clear()
  })

  it('redireciona para login quando response e 401', async () => {
    const error = createAxiosError(401, '/alerts', 'Unauthenticated')

    await expectApiError(() => handler(error))

    expect(authStore.logout).toHaveBeenCalled()
    expect(routerPushSpy).toHaveBeenCalledWith({
      name: 'login',
      query: { redirect: expect.any(String) },
    })
  })

  it('nao redireciona para login em 401 vindo do proprio endpoint de logout', async () => {
    const error = createAxiosError(401, '/auth/logout', 'Unauthenticated')

    await expectApiError(() => handler(error))

    expect(authStore.logout).not.toHaveBeenCalled()
    expect(routerPushSpy).not.toHaveBeenCalledWith({ name: 'login' })
  })

  it('retorna mensagem amigavel para 403', async () => {
    const error = createAxiosError(403, '/admin', 'Forbidden')

    const thrown = await expectApiError(() => handler(error))

    expect(thrown.status).toBe(403)
    expect(thrown.message).toBe('Você não tem permissão para realizar esta ação.')
  })

  it('retorna mensagem amigavel para 404', async () => {
    const error = createAxiosError(404, '/missing', 'Not Found')

    const thrown = await expectApiError(() => handler(error))

    expect(thrown.status).toBe(404)
    expect(thrown.message).toBe('Recurso não encontrado.')
  })

  it('retorna mensagem amigavel para 429', async () => {
    const error = createAxiosError(429, '/rate-limited', 'Too Many Requests')

    const thrown = await expectApiError(() => handler(error))

    expect(thrown.status).toBe(429)
    expect(thrown.message).toBe('Muitas tentativas. Aguarde um momento e tente novamente.')
  })

  it('retorna mensagem padrao para status desconhecido', async () => {
    const error = createAxiosError(500, '/error', 'Internal Server Error')

    const thrown = await expectApiError(() => handler(error))

    expect(thrown.status).toBe(500)
    expect(thrown.message).toBe('Erro inesperado na comunicação com o servidor. Tente novamente.')
  })

  async function expectApiError(fn: () => unknown): Promise<ApiError> {
    try {
      fn()
      throw new Error('Expected function to throw')
    } catch (error) {
      if (error instanceof ApiError) {
        return error
      }
      throw error
    }
  }

  function createAxiosError(
    status: number,
    url: string,
    message: string,
  ): AxiosError<{ message: string; errors?: Record<string, string[]> }> {
    const error = new Error(message) as AxiosError<{
      message: string
      errors?: Record<string, string[]>
    }>
    error.config = { url } as InternalAxiosRequestConfig
    error.response = {
      status,
      data: { message },
    } as AxiosResponse<{ message: string; errors?: Record<string, string[]> }>

    return error
  }
})
