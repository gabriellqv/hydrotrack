<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { api, ApiError } from '@/services/api'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import { Droplets } from 'lucide-vue-next'

/**
 * View de Autenticação.
 *
 * Exibe o formulário de login e faz a interface com o AuthStore
 * para requisição de token Sanctum. Trata erros visuais (401, 422, etc).
 */

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
    })

    authStore.setToken(data.token)
    await authStore.fetchUser()

    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.message
    } else {
      error.value = 'Erro inesperado. Tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-surface">
    <div class="w-full max-w-md animate-fade-in">
      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <div class="flex items-center justify-center h-16 w-16 rounded-2xl bg-primary-600/20 mb-4">
          <Droplets class="h-8 w-8 text-primary-400" />
        </div>
        <h1 class="text-2xl font-bold text-text-heading">HydroTrack</h1>
        <p class="text-sm text-text-muted mt-1">Monitoramento Hídrico Inteligente</p>
      </div>

      <!-- Formulário -->
      <div class="rounded-xl border border-border bg-surface-card p-8 shadow-card">
        <h2 class="text-lg font-semibold text-text-heading mb-6">Entrar na plataforma</h2>

        <div
          v-if="error"
          class="mb-4 rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-3 text-sm text-red-400"
        >
          {{ error }}
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <BaseInput
            v-model="email"
            label="E-mail"
            type="email"
            placeholder="admin@hydrotrack.com"
          />

          <BaseInput v-model="password" label="Senha" type="password" placeholder="••••••••" />

          <BaseButton type="submit" size="lg" :loading="loading" class="w-full mt-2">
            Entrar
          </BaseButton>
        </form>

        <p class="text-xs text-text-muted text-center mt-6">Demo: admin@hydrotrack.com / admin123</p>
      </div>
    </div>
  </div>
</template>
