<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { api, ApiError } from '@/services/api'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

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

  // Validação client-side customizada (substitui tooltips nativos do browser)
  const errors: string[] = []
  if (!email.value) {
    errors.push('O campo e-mail é obrigatório.')
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    errors.push('Informe um endereço de e-mail válido.')
  }
  if (!password.value) {
    errors.push('O campo senha é obrigatório.')
  }
  if (errors.length) {
    error.value = errors.join('\n')
    loading.value = false
    return
  }

  try {
    const { data } = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
    })

    authStore.setToken(data.token)
    await authStore.fetchUser()

    const redirect = (route.query.redirect as string) || '/'
    const isInternalRedirect = redirect.startsWith('/') && !redirect.startsWith('//')
    router.push(isInternalRedirect ? redirect : '/')

    const toast = useToastStore()
    toast.success('Bem-vindo(a) de volta!')
  } catch (e) {
    if (e instanceof ApiError) {
      if (e.errors) {
        error.value = Object.values(e.errors).flat().join('\n')
      } else {
        error.value = e.message
      }
    } else {
      error.value = 'Erro inesperado. Tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-dvh items-center justify-center bg-surface px-4">
    <div class="w-full max-w-md animate-fade-in">
      <!-- Logo -->
      <div class="flex flex-col items-center mb-8">
        <img src="/favicon.svg" alt="HydroTrack" class="h-16 w-16 mb-4 drop-shadow-lg" />
        <h1 class="text-2xl font-bold text-text-heading">HydroTrack</h1>
        <p class="text-sm text-text-muted mt-1">Monitoramento Hídrico Inteligente</p>
      </div>

      <!-- Formulário -->
      <div class="rounded-xl border border-border bg-surface-card p-6 sm:p-8 shadow-card">
        <h2 class="text-lg font-semibold text-text-heading mb-6">Entrar na plataforma</h2>

        <div
          v-if="error"
          class="mb-4 rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-3 text-sm text-red-400"
          style="white-space: pre-line"
        >
          {{ error }}
        </div>

        <form @submit.prevent="handleLogin" novalidate class="space-y-4">
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

        <p class="text-xs text-text-muted text-center mt-6">
          Use as credenciais de teste geradas pelo seeder.
        </p>
      </div>
    </div>
  </div>
</template>
