<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import AppSidebar from '@/components/AppSidebar.vue'
import ScrollToTop from '@/components/ScrollToTop.vue'
import ToastContainer from '@/components/ToastContainer.vue'
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
  <!-- Login: sem sidebar -->
  <template v-if="route.name === 'login'">
    <RouterView />
  </template>

  <!-- App: com sidebar -->
  <template v-else>
    <div class="min-h-screen bg-surface">
      <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

      <div class="flex flex-col min-h-screen lg:ml-[var(--sidebar-width)]">
        <!-- Header mobile -->
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

        <!-- Conteúdo principal -->
        <main class="flex-1 p-4 lg:p-8">
          <RouterView />
        </main>
      </div>

      <ScrollToTop />
    </div>
  </template>

  <ToastContainer />
</template>
