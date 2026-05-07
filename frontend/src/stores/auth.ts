import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'
import { api } from '@/services/api'
import router from '@/router'
import { useToastStore } from '@/stores/toast'

/**
 * Store de autenticação (Pinia).
 *
 * Gerencia o estado do usuário logado, o token Sanctum persistido
 * em localStorage e as funções de login/logout da API.
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
    router.push({ name: 'login' })

    const toast = useToastStore()
    toast.info('Sessão encerrada de forma segura.')
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      loading.value = true
      const { data } = await api.get<User>('/auth/me')
      user.value = data
    } catch {
      logout()
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
