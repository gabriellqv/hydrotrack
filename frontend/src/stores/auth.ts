import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'
import { api } from '@/services/api'
import { useToastStore } from '@/stores/toast'

/**
 * Store de autenticacao (Pinia).
 *
 * Gerencia o estado do usuario logado, o token Sanctum persistido
 * em localStorage e as funcoes de login/logout da API.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<User | null>(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  async function logout() {
    if (token.value) {
      try {
        await api.post('/auth/logout')
      } catch {
        // ignora erro no logout
      }
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')

    const toast = useToastStore()
    toast.info('Sessao encerrada de forma segura.')
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      loading.value = true
      const { data } = await api.get<User>('/auth/me')
      user.value = data
    } catch {
      await logout()
    } finally {
      loading.value = false
    }
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    setToken,
    logout,
    fetchUser,
  }
})
