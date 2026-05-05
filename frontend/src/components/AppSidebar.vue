<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { LayoutDashboard, Droplets, Map, Bell, LogOut, Sun, Moon } from 'lucide-vue-next'

/**
 * Sidebar de navegação principal do HydroTrack.
 *
 * Exibe os links de navegação com ícones Lucide, destaca a rota ativa,
 * fornece o botão de toggle de tema e o botão de logout no rodapé.
 */
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
</script>

<template>
  <aside
    class="fixed left-0 top-0 h-screen w-64 flex flex-col border-r backdrop-blur-2xl"
    :style="{
      background: `linear-gradient(to bottom, var(--sidebar-from), var(--sidebar-to))`,
      borderColor: 'var(--sidebar-border)',
    }"
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-border">
      <Droplets class="h-8 w-8 text-primary-400" />
      <div>
        <h1 class="text-lg font-bold text-text-heading">HydroTrack</h1>
        <p class="text-xs text-text-muted">Monitoramento Hídrico</p>
      </div>
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
          class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600/20 text-primary-400 text-sm font-bold"
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
