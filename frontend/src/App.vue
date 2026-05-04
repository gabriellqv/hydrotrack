<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppSidebar from '@/components/AppSidebar.vue'

const route = useRoute()
const authStore = useAuthStore()

onMounted(async () => {
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
    <div class="flex min-h-screen">
      <AppSidebar />
      <main class="flex-1 ml-64 p-8">
        <RouterView />
      </main>
    </div>
  </template>
</template>
