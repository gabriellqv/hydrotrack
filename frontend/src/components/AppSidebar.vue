<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { LayoutDashboard, Droplets, Map, Bell, LogOut } from 'lucide-vue-next'

/**
 * Sidebar de navegação principal do HydroTrack.
 *
 * Exibe os links de navegação com ícones Lucide, destaca a rota ativa
 * e fornece o botão de logout no rodapé.
 */
const route = useRoute()
const authStore = useAuthStore()

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
    class="fixed left-0 top-0 h-screen w-64 flex flex-col border-r border-white/10 bg-gradient-to-b from-slate-800/70 to-slate-900/70 backdrop-blur-2xl"
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700/50">
      <Droplets class="h-8 w-8 text-primary-400" />
      <div>
        <h1 class="text-lg font-bold text-white">HydroTrack</h1>
        <p class="text-xs text-slate-500">Monitoramento Hídrico</p>
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
            : 'text-slate-400 hover:bg-surface-hover hover:text-slate-200',
        ]"
      >
        <component :is="item.icon" class="h-5 w-5" />
        {{ item.label }}
      </RouterLink>
    </nav>

    <!-- Rodapé com usuário e logout -->
    <div class="border-t border-slate-700/50 px-4 py-4">
      <div class="flex items-center gap-3">
        <!-- Avatar -->
        <div
          class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600/20 text-primary-400 text-sm font-bold"
        >
          {{ authStore.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-200 truncate">
            {{ authStore.user?.name ?? 'Carregando...' }}
          </p>
          <p class="text-xs text-slate-500 truncate">
            {{ authStore.user?.role === 'admin' ? 'Administrador' : 'Operador' }}
          </p>
        </div>

        <!-- Botão de logout -->
        <button
          @click="authStore.logout()"
          class="rounded-lg p-2 text-slate-400 hover:bg-surface-hover hover:text-red-400 transition-colors"
          title="Sair"
        >
          <LogOut class="h-4 w-4" />
        </button>
      </div>
    </div>
  </aside>
</template>
