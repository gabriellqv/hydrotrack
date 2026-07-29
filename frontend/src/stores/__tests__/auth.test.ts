import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth'
import { api } from '@/services/api'

/**
 * Testes unitarios da store de autenticacao.
 *
 * Validam persistencia do token, logout e carregamento do usuario autenticado.
 */

vi.mock('@/services/api')

const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
  localStorageMock.getItem.mockReturnValue(null)
  vi.stubGlobal('localStorage', localStorageMock)
})

describe('useAuthStore', () => {
  it('inicia nao autenticada sem token', () => {
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

  it('carrega usuario autenticado da API', async () => {
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
