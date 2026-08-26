import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useIsAdmin } from '@/composables/useIsAdmin'

/**
 * Testes do composable useIsAdmin.
 *
 * Validam a derivação de permissão de administrador a partir do usuário
 * autenticado na store de autenticação.
 */
describe('useIsAdmin', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('retorna true quando usuário tem role admin', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, name: 'Admin', email: 'admin@test.com', role: 'admin' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(true)
  })

  it('retorna false quando usuário tem role operator', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 2, name: 'Operator', email: 'operator@test.com', role: 'operator' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)
  })

  it('retorna false quando usuário não está autenticado', () => {
    const authStore = useAuthStore()
    authStore.user = null

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)
  })

  it('reage a mudanças de role do usuário', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 3, name: 'User', email: 'user@test.com', role: 'operator' }

    const { isAdmin } = useIsAdmin()

    expect(isAdmin.value).toBe(false)

    authStore.user.role = 'admin'

    expect(isAdmin.value).toBe(true)
  })
})
