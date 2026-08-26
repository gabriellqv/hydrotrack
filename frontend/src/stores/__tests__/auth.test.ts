import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth'
import { api } from '@/services/api'

/**
 * Testes unitários da store de autenticação.
 *
 * Validam persistência do token, logout e carregamento do usuário autenticado.
 */

vi.mock('@/services/api')

const localStorageMock = {
  getItem: vi.fn<(key: string) => string | null>(),
  setItem: vi.fn<(key: string, value: string) => void>(),
  removeItem: vi.fn<(key: string) => void>(),
  clear: vi.fn<() => void>(),
  key: vi.fn<(index: number) => string | null>(),
  length: 0,
} as unknown as Storage

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
  vi.mocked(localStorageMock.getItem).mockReturnValue(null)
  vi.stubGlobal('localStorage', localStorageMock)
})

describe('useAuthStore', () => {
  it('inicia não autenticada sem token', () => {
    const store = useAuthStore()

    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('define token e persiste no localStorage', () => {
    const store = useAuthStore()

    store.setToken('fake-token')

    expect(store.token).toBe('fake-token')
    expect(store.isAuthenticated).toBe(true)
    expect(localStorageMock.setItem).toHaveBeenCalledWith('auth_token', 'fake-token')
  })

  it('carrega usuário autenticado da API', async () => {
    const mockUser = { id: 1, name: 'Admin', email: 'admin@hydrotrack.com', role: 'admin' }
    vi.mocked(api.get).mockResolvedValue({ data: mockUser })

    const store = useAuthStore()
    store.setToken('fake-token')

    await store.fetchUser()

    expect(store.user).toEqual(mockUser)
    expect(store.loading).toBe(false)
  })

  it('limpa token e usuario ao fazer logout', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: {} })

    const store = useAuthStore()
    store.setToken('fake-token')
    store.user = { id: 1, name: 'Admin', email: 'admin@hydrotrack.com', role: 'admin' }

    await store.logout()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorageMock.removeItem).toHaveBeenCalledWith('auth_token')
    expect(api.post).toHaveBeenCalledWith('/auth/logout')
  })
})
