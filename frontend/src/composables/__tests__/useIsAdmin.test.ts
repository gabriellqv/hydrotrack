import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useIsAdmin } from '@/composables/useIsAdmin'

/**
 * Testes do composable useIsAdmin.
 *
 * Validam a derivacao de permissao de administrador a partir do usuario
 * autenticado na store de autenticacao.
 */
describe('useIsAdmin', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('retorna true quando usuario tem role admin', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, name: 'Admin', email: 'admin@test.com', role: 'admin' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(true)
  })

  it('retorna false quando usuario tem role operator', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 2, name: 'Operator', email: 'operator@test.com', role: 'operator' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)
  })

  it('retorna false quando usuario nao esta autenticado', () => {
    const authStore = useAuthStore()
    authStore.user = null

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)
  })

  it('reage a mudancas de role do usuario', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 3, name: 'User', email: 'user@test.com', role: 'operator' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)

    authStore.user.role = 'admin'

    expect(isAdmin.value).toBe(true)
  })
})
