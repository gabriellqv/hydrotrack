<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { LayoutDashboard, Map, Bell, LogOut, Sun, Moon, X, Droplets } from 'lucide-vue-next'

/**
 * Sidebar de navegação principal do HydroTrack.
 *
 * Exibe os links de navegação com ícones Lucide, destaca a rota ativa,
 * fornece o botão de toggle de tema e o botão de logout no rodapé.
 * Em telas menores que lg, funciona como um drawer overlay com backdrop.
 *
 * @prop {boolean} open - Controla visibilidade no mobile
 * @emits close - Emitido quando o drawer deve ser fechado
 */
defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const route = useRoute()
const authStore = useAuthStore()
const { isDark, toggleTheme } = useTheme()

const navItems = [
  { name: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, path: '/' },
  { name: 'hydrometers', label: 'Hidrômetros', icon: Droplets, path: '/hydrometers' },
  { name: 'map', label: 'Mapa', icon: Map, path: '/map' },
  { name: 'alerts', label: 'Alertas', icon: Bell, path: '/alerts' },
]

const currentRoute = computed(() => route.name)

/** Fecha o drawer ao navegar (mobile) */
watch(
  () => route.path,
  () => emit('close'),
)
</script>

<template>
  <!-- Backdrop mobile -->
  <Transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
      @click="emit('close')"
    />
  </Transition>

  <!-- Sidebar -->
  <aside
    :class="[
      'fixed top-0 left-0 z-50 h-dvh flex flex-col border-r backdrop-blur-2xl',
      'transition-transform duration-300 ease-in-out',
      'lg:translate-x-0',
      open ? 'translate-x-0' : '-translate-x-full',
    ]"
    :style="{
      width: 'var(--sidebar-width)',
      background: `linear-gradient(to bottom, var(--sidebar-from), var(--sidebar-to))`,
      borderColor: 'var(--sidebar-border)',
    }"
  >
    <!-- Logo + Close button (mobile) -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-border">
      <img src="/favicon.svg" alt="HydroTrack" class="h-8 w-8 drop-shadow-sm" />
      <div class="flex-1">
        <h1 class="text-lg font-bold text-text-heading">HydroTrack</h1>
        <p class="text-xs text-text-muted">Monitoramento Hídrico</p>
      </div>
      <button
        @click="emit('close')"
        class="lg:hidden rounded-lg p-1.5 text-text-muted hover:bg-surface-hover hover:text-text-heading transition-colors"
        title="Fechar menu"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- Navegação -->
    <nav class="flex-1 px-3 py-4 space-y-1">
      <RouterLink
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        :class="[
          'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
          currentRoute === item.name
            ? 'bg-primary-600/20 text-primary-400 border-r-2 border-primary-400'
            : 'text-text-muted hover:bg-surface-hover hover:text-text-heading',
        ]"
      >
        <component :is="item.icon" class="h-5 w-5" />
        {{ item.label }}
      </RouterLink>
    </nav>

    <!-- Rodapé com usuário, toggle de tema e logout -->
    <div class="border-t border-border px-4 py-4 space-y-3">
      <!-- Toggle de tema -->
      <button
        @click="toggleTheme"
        class="flex items-center gap-3 w-full rounded-lg px-3 py-2 text-sm font-medium text-text-muted hover:bg-surface-hover hover:text-text-heading transition-colors"
        :title="isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro'"
      >
        <Sun v-if="isDark" class="h-4 w-4" />
        <Moon v-else class="h-4 w-4" />
        {{ isDark ? 'Tema Claro' : 'Tema Escuro' }}
      </button>

      <!-- Usuário e logout -->
      <div class="flex items-center gap-3">
        <!-- Avatar -->
        <div
          class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600/20 text-primary-400 text-sm font-bold shrink-0"
        >
          {{ authStore.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-text-heading truncate">
            {{ authStore.user?.name ?? 'Carregando...' }}
          </p>
          <p class="text-xs text-text-muted truncate">
            {{ authStore.user?.role === 'admin' ? 'Administrador' : 'Operador' }}
          </p>
        </div>

        <!-- Botão de logout -->
        <button
          @click="authStore.logout()"
          class="rounded-lg p-2 text-text-muted hover:bg-surface-hover hover:text-red-400 transition-colors"
          title="Sair"
        >
          <LogOut class="h-4 w-4" />
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
