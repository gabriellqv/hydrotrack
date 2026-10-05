<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute } from 'vue-router'
import { LayoutGrid, Droplets, Map, Bell, Menu } from 'lucide-vue-next'
import { useDashboardStore } from '@/stores/dashboard'

/**
 * Barra de navegação inferior para mobile.
 *
 * Destaca a aba ativa (com indicador neon), exibe badge de alertas pendentes
 * e respeita a safe-area do dispositivo.
 *
 * @prop {boolean} menuOpen - Indica se o menu lateral está atualmente aberto
 * @emits toggleMenu - Emitido ao clicar no botão de menu
 * @emits closeMenu - Emitido para fechar o menu ao selecionar outra rota
 */
const props = withDefaults(
  defineProps<{
    menuOpen?: boolean
  }>(),
  {
    menuOpen: false,
  },
)

const emit = defineEmits<{
  toggleMenu: []
  closeMenu: []
}>()

const route = useRoute()
const dashboardStore = useDashboardStore()

const pendingAlerts = computed(() => dashboardStore.summary?.pending_alerts ?? 0)

interface NavItem {
  id: string
  label: string
  icon: Component
  path?: string
  isMenu?: boolean
}

const items: NavItem[] = [
  { id: 'home', label: 'Início', icon: LayoutGrid, path: '/' },
  { id: 'hydrometers', label: 'Hidrômetros', icon: Droplets, path: '/hydrometers' },
  { id: 'map', label: 'Mapa', icon: Map, path: '/map' },
  { id: 'alerts', label: 'Alertas', icon: Bell, path: '/alerts' },
  { id: 'menu', label: 'Menu', icon: Menu, isMenu: true },
]

function isItemActive(item: NavItem): boolean {
  if (props.menuOpen) {
    return item.isMenu === true
  }

  if (item.isMenu) return false

  if (item.path === '/') {
    return route.path === '/'
  }

  if (item.path) {
    return route.path.startsWith(item.path)
  }

  return false
}

function handleItemClick(item: NavItem) {
  if (item.isMenu) {
    emit('toggleMenu')
  } else {
    emit('closeMenu')
  }
}
</script>

<template>
  <nav
    class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-surface-card/95 backdrop-blur-2xl border-t border-border/80 shadow-[0_-6px_28px_rgba(0,0,0,0.35)] select-none transition-colors duration-300 rounded-t-2xl overflow-hidden"
    style="padding-bottom: max(0.35rem, env(safe-area-inset-bottom, 0px))"
    aria-label="Navegação móvel"
  >
    <div class="grid grid-cols-5 h-[64px] items-stretch px-1 relative">
      <template v-for="item in items" :key="item.id">
        <!-- Rota de Navegação (RouterLink) -->
        <RouterLink
          v-if="!item.isMenu"
          :to="item.path!"
          @click="handleItemClick(item)"
          :class="[
            'group relative flex flex-col items-center justify-center py-1 transition-all duration-200 active:scale-95 overflow-hidden',
            isItemActive(item) ? 'text-primary-400' : 'text-text-muted hover:text-text-heading',
          ]"
        >
          <!-- 1. Linha Superior Neon Indicadora (Ativa & Hover) -->
          <div
            :class="[
              'absolute top-0 left-1/2 -translate-x-1/2 h-[3px] rounded-full transition-all duration-300 ease-out',
              isItemActive(item)
                ? 'w-12 bg-gradient-to-r from-sky-400 via-primary-400 to-sky-400 opacity-100 shadow-[0_0_12px_rgba(56,189,248,0.95),0_0_24px_rgba(56,189,248,0.5)]'
                : 'w-6 bg-primary-400/60 opacity-0 group-hover:opacity-100 group-hover:w-10 group-hover:shadow-[0_0_8px_rgba(56,189,248,0.6)]',
            ]"
          />

          <!-- 2. Cone de Luz / Spotlight Ambiente Superior (Ativo & Hover) -->
          <div
            :class="[
              'absolute inset-0 pointer-events-none transition-opacity duration-300 ease-out',
              isItemActive(item) ? 'opacity-100' : 'opacity-0 group-hover:opacity-50',
            ]"
            style="
              background: radial-gradient(
                ellipse 70% 85% at 50% 0%,
                rgba(56, 189, 248, 0.32) 0%,
                rgba(14, 165, 233, 0.12) 45%,
                transparent 75%
              );
            "
          />

          <!-- 3. Ícone com Brilho Dinâmico -->
          <div class="relative flex items-center justify-center z-10 my-0.5">
            <component
              :is="item.icon"
              :class="[
                'h-5 w-5 transition-all duration-200',
                isItemActive(item)
                  ? 'text-primary-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.6)] scale-105'
                  : 'text-text-muted group-hover:text-text-heading group-hover:scale-105',
              ]"
            />

            <!-- Badge de Alertas Pendentes -->
            <span
              v-if="item.id === 'alerts' && pendingAlerts > 0"
              class="absolute -top-1.5 -right-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white shadow-sm ring-2 ring-surface animate-pulse"
            >
              {{ pendingAlerts > 9 ? '9+' : pendingAlerts }}
            </span>
          </div>

          <!-- 4. Rótulo / Categoria -->
          <span
            :class="[
              'text-[10.5px] tracking-tight leading-tight truncate max-w-full px-1 transition-all duration-200 z-10',
              isItemActive(item)
                ? 'font-semibold text-primary-400 drop-shadow-[0_0_6px_rgba(56,189,248,0.3)]'
                : 'font-medium text-text-muted group-hover:text-text-heading',
            ]"
          >
            {{ item.label }}
          </span>
        </RouterLink>

        <!-- Botão de Ação Menu -->
        <button
          v-else
          type="button"
          @click="handleItemClick(item)"
          :class="[
            'group relative flex flex-col items-center justify-center py-1 transition-all duration-200 active:scale-95 cursor-pointer overflow-hidden',
            isItemActive(item) ? 'text-primary-400' : 'text-text-muted hover:text-text-heading',
          ]"
          :aria-expanded="props.menuOpen"
          aria-label="Abrir menu lateral"
        >
          <!-- 1. Linha Superior Neon Indicadora (Ativa & Hover) -->
          <div
            :class="[
              'absolute top-0 left-1/2 -translate-x-1/2 h-[3px] rounded-full transition-all duration-300 ease-out',
              isItemActive(item)
                ? 'w-12 bg-gradient-to-r from-sky-400 via-primary-400 to-sky-400 opacity-100 shadow-[0_0_12px_rgba(56,189,248,0.95),0_0_24px_rgba(56,189,248,0.5)]'
                : 'w-6 bg-primary-400/60 opacity-0 group-hover:opacity-100 group-hover:w-10 group-hover:shadow-[0_0_8px_rgba(56,189,248,0.6)]',
            ]"
          />

          <!-- 2. Cone de Luz / Spotlight Ambiente Superior (Ativo & Hover) -->
          <div
            :class="[
              'absolute inset-0 pointer-events-none transition-opacity duration-300 ease-out',
              isItemActive(item) ? 'opacity-100' : 'opacity-0 group-hover:opacity-50',
            ]"
            style="
              background: radial-gradient(
                ellipse 70% 85% at 50% 0%,
                rgba(56, 189, 248, 0.32) 0%,
                rgba(14, 165, 233, 0.12) 45%,
                transparent 75%
              );
            "
          />

          <!-- 3. Ícone com Brilho Dinâmico -->
          <div class="relative flex items-center justify-center z-10 my-0.5">
            <component
              :is="item.icon"
              :class="[
                'h-5 w-5 transition-all duration-200',
                isItemActive(item)
                  ? 'text-primary-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.6)] scale-105'
                  : 'text-text-muted group-hover:text-text-heading group-hover:scale-105',
              ]"
            />
          </div>

          <!-- 4. Rótulo / Categoria -->
          <span
            :class="[
              'text-[10.5px] tracking-tight leading-tight truncate max-w-full px-1 transition-all duration-200 z-10',
              isItemActive(item)
                ? 'font-semibold text-primary-400 drop-shadow-[0_0_6px_rgba(56,189,248,0.3)]'
                : 'font-medium text-text-muted group-hover:text-text-heading',
            ]"
          >
            {{ item.label }}
          </span>
        </button>
      </template>
    </div>
  </nav>
</template>
