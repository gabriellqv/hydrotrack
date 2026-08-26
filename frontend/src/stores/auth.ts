import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'
import { api } from '@/services/api'
import { useToastStore } from '@/stores/toast'

/**
 * Store de autenticação (Pinia).
 *
 * Gerencia o estado do usuário logado, o token Sanctum persistido
 * em `localStorage` e as funções de login/logout da API.
 */
export const useAuthStore = defineStore('auth', () => {
  /**
   * Token de autenticação Sanctum, persistido em `localStorage`.
   */
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  /**
   * Dados do usuário autenticado.
   */
  const user = ref<User | null>(null)

  /**
   * Estado de carregamento durante operações assíncronas de autenticação.
   */
  const loading = ref(false)

  /**
   * Indica se há um token presente.
   */
  const isAuthenticated = computed(() => !!token.value)

  /**
   * Armazena o token no estado e no `localStorage`.
   *
   * @param newToken - Token Bearer retornado pela API.
   */
  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  /**
   * Encerra a sessão, notifica a API, limpa o estado/`localStorage` e exibe toast informativo.
   */
  async function logout() {
    if (token.value) {
      try {
        await api.post('/auth/logout')
      } catch {
        // Garante que o estado local seja limpo mesmo se a API de logout falhar.
      }
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')

    const toast = useToastStore()
    toast.info('Sessão encerrada de forma segura.')
  }

  /**
   * Recupera os dados do usuário autenticado.
   * Em caso de falha, executa `logout()`.
   */
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
