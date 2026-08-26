/** * Componente raiz da aplicação. * * Gerencia autenticação, tema, layout responsivo, Suspense e
skeleton condicional. */

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import AppSidebar from '@/components/AppSidebar.vue'
import ScrollToTop from '@/components/ScrollToTop.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import PageSkeleton from '@/components/ui/PageSkeleton.vue'
import DashboardSkeleton from '@/components/ui/DashboardSkeleton.vue'
import { Menu } from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const { initTheme } = useTheme()
const sidebarOpen = ref(false)

onMounted(async () => {
  initTheme()
  if (authStore.token) {
    await authStore.fetchUser()
  }
})
</script>

<template>
  <template v-if="route.name === 'login'">
    <RouterView />
  </template>

  <template v-else>
    <div class="min-h-screen bg-surface">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <div class="flex flex-col min-h-screen lg:ml-[var(--sidebar-width)]">
        <header
          class="sticky top-0 z-30 flex items-center gap-3 px-4 py-3 border-b border-border bg-surface/80 backdrop-blur-xl lg:hidden"
        >
          <button
            @click="sidebarOpen = true"
            class="rounded-lg p-2 text-text-muted hover:bg-surface-hover hover:text-text-heading transition-colors"
            title="Abrir menu"
          >
            <Menu class="h-5 w-5" />
          </button>
          <img src="/favicon.svg" alt="HydroTrack" class="h-6 w-6" />
          <span class="text-sm font-bold text-text-heading">HydroTrack</span>
        </header>

        <main class="flex-1 p-4 lg:p-8">
          <RouterView v-slot="{ Component }">
            <template v-if="Component">
              <Suspense>
                <component :is="Component" />
                <template #fallback>
                  <DashboardSkeleton v-if="route.name === 'dashboard'" />
                  <PageSkeleton v-else />
                </template>
              </Suspense>
            </template>
          </RouterView>
        </main>
      </div>

      <ScrollToTop />
    </div>
  </template>

  <ToastContainer />
</template>
