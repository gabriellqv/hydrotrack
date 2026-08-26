import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Retorna um flag reativo que indica se o usuário autenticado possui role `admin`.
 * Baseia-se no estado do `useAuthStore`.
 *
 * @returns Objeto com a propriedade reativa `isAdmin`.
 */
export function useIsAdmin() {
  const authStore = useAuthStore()

  const isAdmin = computed(() => authStore.user?.role === 'admin')

  return { isAdmin }
}
