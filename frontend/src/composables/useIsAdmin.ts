import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable que verifica se o usuário autenticado possui role de administrador.
 *
 * Uso: condicionar a renderização de botões de criação, edição e exclusão
 * para que apenas administradores os vejam.
 *
 * @returns {{ isAdmin: ComputedRef<boolean> }}
 *
 * @example
 * ```vue
 * <script setup>
 * const { isAdmin } = useIsAdmin()
 * </script>
 * <template>
 *   <BaseButton v-if="isAdmin" @click="openCreateModal">Novo Hidrômetro</BaseButton>
 * </template>
 * ```
 */
export function useIsAdmin() {
  const authStore = useAuthStore()

  const isAdmin = computed(() => authStore.user?.role === 'admin')

  return { isAdmin }
}
