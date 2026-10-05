<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import { useTheme } from '@/composables/useTheme'
import AppSidebar from '@/components/AppSidebar.vue'
import MobileBottomNav from '@/components/MobileBottomNav.vue'
import ScrollToTop from '@/components/ScrollToTop.vue'
import ToastContainer from '@/components/ToastContainer.vue'

const route = useRoute()
const authStore = useAuthStore()
const dashboardStore = useDashboardStore()
const { initTheme } = useTheme()
const sidebarOpen = ref(false)

onMounted(async () => {
  initTheme()
  if (authStore.token) {
    await authStore.fetchUser()
    // O badge de alertas no mobile depende do summary; falhas são tratadas
    // pelo interceptor da API e não devem gerar unhandled rejection.
    dashboardStore.fetchSummary().catch(() => {})
  }
})
</script>

<template>
  <!-- Login: sem sidebar -->
  <template v-if="route.name === 'login'">
    <RouterView />
  </template>

  <!-- App: com sidebar -->
  <template v-else>
    <div class="min-h-screen bg-surface">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <div class="flex flex-col min-h-screen lg:ml-[var(--sidebar-width)]">
        <!-- Header mobile (Navbar) -->
        <header
          class="sticky top-0 z-30 flex items-center gap-2.5 px-4 py-3 border-b border-border bg-surface/80 backdrop-blur-xl lg:hidden"
        >
          <img src="/logo.png" alt="HydroTrack" class="h-8 w-8 object-contain drop-shadow" />
          <span class="text-base font-bold text-text-heading">HydroTrack</span>
        </header>

        <!-- Conteúdo principal -->
        <main
          :class="[
            'flex-1 flex flex-col w-full min-h-0 pb-20 lg:pb-0',
            route.name === 'map' ? 'p-3 lg:p-4' : 'p-4 lg:p-6 xl:p-8',
          ]"
        >
          <div
            :class="[
              'w-full mx-auto flex-1 flex flex-col min-h-0',
              route.name === 'map' ? 'max-w-[1920px]' : 'max-w-[1680px]',
            ]"
          >
            <RouterView v-slot="{ Component }">
              <template v-if="Component">
                <KeepAlive :max="8">
                  <component
                    :is="Component"
                    :key="
                      route.name === 'hydrometer-detail' ? route.fullPath : route.name || route.path
                    "
                  />
                </KeepAlive>
              </template>
            </RouterView>
          </div>
        </main>
      </div>

      <!-- Barra de navegação inferior mobile (Bottom Navigation Bar) -->
      <MobileBottomNav
        :menu-open="sidebarOpen"
        @toggle-menu="sidebarOpen = !sidebarOpen"
        @close-menu="sidebarOpen = false"
      />

      <ScrollToTop />
    </div>
  </template>

  <ToastContainer />
</template>
