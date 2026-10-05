<script setup lang="ts">
import { watch, onUnmounted, onMounted } from 'vue'
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
const props = defineProps<{
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

function isActive(item: (typeof navItems)[0]) {
  if (item.path === '/') {
    return route.path === '/'
  }
  return route.path.startsWith(item.path)
}

/** Fecha o drawer ao navegar (mobile) */
watch(
  () => route.path,
  () => emit('close'),
)

/** Previne scroll do body quando o menu mobile está aberto */
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.body.classList.add('overflow-hidden')
    } else {
      document.body.classList.remove('overflow-hidden')
    }
  },
)

/** Fecha o menu automaticamente se a tela for redimensionada para desktop */
function handleResize() {
  if (window.innerWidth >= 1024 && props.open) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  document.body.classList.remove('overflow-hidden')
})
</script>

<template>
  <!-- Backdrop mobile -->
  <Transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-[45] bg-black/60 backdrop-blur-sm lg:hidden"
      @click="emit('close')"
    />
  </Transition>

  <!-- Sidebar -->
  <aside
    :class="[
      'fixed top-0 left-0 z-50 h-dvh w-full lg:w-[var(--sidebar-width)] flex flex-col border-r backdrop-blur-2xl',
      'transition-transform duration-300 ease-in-out',
      open ? 'translate-y-0' : '-translate-y-full lg:translate-y-0',
    ]"
    :style="{
      background: `linear-gradient(to bottom, var(--sidebar-from), var(--sidebar-to))`,
      borderColor: 'var(--sidebar-border)',
    }"
  >
    <!-- Logo + Close button (mobile) -->
    <div class="flex items-center justify-between px-6 py-5 border-b border-border">
      <div class="flex items-center gap-3.5">
        <img src="/logo.png" alt="HydroTrack" class="h-11 w-11 object-contain drop-shadow-md" />
        <div>
          <h1 class="text-xl font-bold text-text-heading tracking-tight leading-none">HydroTrack</h1>
          <p class="text-xs text-text-muted mt-1">Monitoramento Hídrico</p>
        </div>
      </div>
      <button
        @click="emit('close')"
        class="lg:hidden rounded-lg p-1.5 text-text-muted hover:bg-surface-hover hover:text-text-heading transition-colors cursor-pointer"
        title="Fechar menu"
        aria-label="Fechar menu"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- Navegação -->
    <nav class="flex-1 px-3 py-4 space-y-1.5">
      <RouterLink
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        :class="[
          'group relative flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition-all duration-200 overflow-visible',
          isActive(item)
            ? (isDark
                ? 'text-white bg-white/[0.08] border border-white/10 shadow-sm'
                : 'text-slate-900 bg-primary-500/15 border border-primary-500/30 shadow-sm')
            : (isDark
                ? 'text-slate-400 hover:text-white hover:bg-white/[0.06] border border-transparent'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/80 border border-transparent'),
        ]"
      >
        <!-- Accent Neon Curvado na Esquerda (Ativo & Hover) idêntico à referência, em azul -->
        <div
          :class="[
            'absolute inset-0 rounded-xl pointer-events-none transition-all duration-300 ease-out',
            isActive(item) ? 'opacity-100' : 'opacity-0 group-hover:opacity-80',
          ]"
          :style="{
            border: '2.5px solid transparent',
            borderLeftColor: isDark ? '#38bdf8' : '#0284c7',
            borderTopColor: isDark ? '#38bdf8' : '#0284c7',
            borderBottomColor: isDark ? '#38bdf8' : '#0284c7',
            WebkitMaskImage: 'linear-gradient(to right, #000 0px, #000 12px, transparent 26px)',
            maskImage: 'linear-gradient(to right, #000 0px, #000 12px, transparent 26px)',
            filter: isDark
              ? 'drop-shadow(0 0 6px rgba(56, 189, 248, 0.95)) drop-shadow(-3px 0 12px rgba(14, 165, 233, 0.75))'
              : 'drop-shadow(0 0 4px rgba(2, 132, 199, 0.5))',
          }"
        />

        <!-- Brilho Ambiente / Spotlight Suave Interno Azul -->
        <div
          :class="[
            'absolute inset-0 rounded-xl pointer-events-none transition-opacity duration-300',
            isDark
              ? (isActive(item)
                  ? 'opacity-100 bg-gradient-to-r from-sky-500/15 via-primary-500/5 to-transparent'
                  : 'opacity-0 group-hover:opacity-100 bg-gradient-to-r from-sky-500/10 via-primary-500/5 to-transparent')
              : (isActive(item)
                  ? 'opacity-100 bg-gradient-to-r from-primary-500/15 via-sky-500/5 to-transparent'
                  : 'opacity-0 group-hover:opacity-100 bg-gradient-to-r from-primary-500/10 via-sky-500/5 to-transparent'),
          ]"
        />

        <!-- Ícone com realce nítido e contraste adaptativo -->
        <component
          :is="item.icon"
          :class="[
            'h-5 w-5 transition-all duration-200 z-10',
            isActive(item)
              ? (isDark
                  ? 'text-primary-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.45)] scale-105'
                  : 'text-primary-600 drop-shadow-sm scale-105')
              : (isDark
                  ? 'text-slate-400 group-hover:text-primary-400 group-hover:scale-105'
                  : 'text-slate-500 group-hover:text-primary-600 group-hover:scale-105'),
          ]"
        />

        <span
          :class="[
            'z-10 transition-colors duration-200',
            isActive(item)
              ? (isDark ? 'font-semibold text-white' : 'font-bold text-slate-900')
              : (isDark
                  ? 'font-medium text-slate-400 group-hover:text-white'
                  : 'font-medium text-slate-600 group-hover:text-slate-900'),
          ]"
        >
          {{ item.label }}
        </span>
      </RouterLink>
    </nav>

    <!-- Rodapé: Perfil do Usuário, Alternador de Tema e Logout -->
    <div
      class="border-t border-border p-3.5 space-y-2.5"
      style="padding-bottom: max(0.875rem, env(safe-area-inset-bottom, 0px));"
    >
      <!-- Informações do usuário logado + botão de alternar tema -->
      <div
        class="flex items-center justify-between gap-2.5 px-1 py-1"
      >
        <!-- Avatar com indicador de status online -->
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="relative shrink-0">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500/25 to-primary-600/35 border border-primary-500/30 text-primary-400 text-xs font-bold shadow-sm"
            >
              {{ authStore.user?.name?.charAt(0)?.toUpperCase() ?? '?' }}
            </div>
            <span
              class="absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-surface"
              title="Conectado"
            ></span>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-text-heading truncate">
              {{ authStore.user?.name ?? 'Carregando...' }}
            </p>
            <p class="text-[10px] text-text-muted truncate">
              {{ authStore.user?.role === 'admin' ? 'Administrador' : 'Operador' }}
            </p>
          </div>
        </div>

        <!-- Botão Alternador de Tema com animação elástica de giro -->
        <button
          @click="toggleTheme"
          class="relative flex items-center justify-center h-8 w-8 rounded-lg text-text-muted hover:text-amber-400 hover:bg-surface-hover/60 active:scale-90 transition-all duration-200 cursor-pointer shrink-0"
          :title="isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro'"
          aria-label="Alternar tema"
        >
          <Transition name="theme-spin" mode="out-in">
            <Sun
              v-if="isDark"
              key="sun"
              class="h-4 w-4 text-amber-400"
            />
            <Moon
              v-else
              key="moon"
              class="h-4 w-4 text-sky-400"
            />
          </Transition>
        </button>
      </div>

      <!-- Botão de Sair com Padrão Pill Arredondado -->
      <button
        @click="authStore.logout()"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-full text-xs font-semibold text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 hover:border-rose-500/30 active:scale-[0.98] transition-all duration-200 cursor-pointer shadow-sm group"
        title="Encerrar sessão"
      >
        <LogOut class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-0.5" />
        <span>Sair da conta</span>
      </button>
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

/* Animação suave e elástica de rotação ao alternar o tema */
.theme-spin-enter-active,
.theme-spin-leave-active {
  transition:
    transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1),
    opacity 0.2s ease;
}

.theme-spin-enter-from {
  transform: rotate(-140deg) scale(0.3);
  opacity: 0;
}

.theme-spin-leave-to {
  transform: rotate(140deg) scale(0.3);
  opacity: 0;
}
</style>
