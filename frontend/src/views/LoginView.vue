<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useTheme } from '@/composables/useTheme'
import { api, ApiError } from '@/services/api'
import BaseButton from '@/components/ui/BaseButton.vue'
import {
  Mail,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  ShieldCheck,
  Sparkles,
  Sun,
  Moon,
  AlertCircle,
  Droplets,
} from 'lucide-vue-next'

/**
 * View de Autenticação Corporativa — HydroTrack.
 *
 * Exibe a tela de login moderna com visual glassmorphic, ambient glow temático de telemetria hídrica,
 * validação prévia de campos, alternador de visibilidade de senha, botão de autopreenchimento de demo
 * e alternador de tema claro/escuro.
 */

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { isDark, toggleTheme } = useTheme()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const demoFilled = ref(false)

/**
 * Credenciais de demonstração. Só são embutidas no build de desenvolvimento
 * e quando explicitamente fornecidas via VITE_DEMO_EMAIL/VITE_DEMO_PASSWORD,
 * evitando expor credenciais do ambiente de produção.
 */
const demoEmail = import.meta.env.VITE_DEMO_EMAIL as string | undefined
const demoPassword = import.meta.env.VITE_DEMO_PASSWORD as string | undefined
const showDemo = import.meta.env.DEV && !!demoEmail && !!demoPassword

function fillDemoCredentials() {
  email.value = demoEmail ?? ''
  password.value = demoPassword ?? ''
  error.value = ''
  demoFilled.value = true
  setTimeout(() => (demoFilled.value = false), 1800)
}

async function handleLogin() {
  loading.value = true
  error.value = ''

  // Validação client-side
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
    router.push(redirect)

    const toast = useToastStore()
    toast.success('Bem-vindo(a) ao HydroTrack!')
  } catch (e) {
    if (e instanceof ApiError) {
      if (e.errors) {
        error.value = Object.values(e.errors).flat().join('\n')
      } else {
        error.value = e.message
      }
    } else {
      error.value = 'Erro de conexão. Verifique sua rede e tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="relative min-h-dvh flex items-center justify-center bg-surface px-4 py-12 overflow-hidden selection:bg-primary-500 selection:text-white"
  >
    <!-- Efeitos de Fundo Ambientais (Glow & Water Ripples) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <!-- Glow superior ciano/azul -->
      <div
        class="absolute -top-32 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-b from-primary-500/15 via-cyan-500/10 to-transparent rounded-full blur-3xl opacity-70"
      ></div>
      <!-- Glow inferior sutil -->
      <div
        class="absolute -bottom-32 right-1/4 w-[500px] h-[400px] bg-gradient-to-t from-blue-600/10 via-indigo-500/5 to-transparent rounded-full blur-3xl opacity-50"
      ></div>

      <!-- Grade vetorial sutil no fundo -->
      <svg
        class="absolute inset-0 w-full h-full opacity-[0.03] dark:opacity-[0.05]"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <pattern id="grid-pattern" width="48" height="48" patternUnits="userSpaceOnUse">
            <path d="M 48 0 L 0 0 0 48" fill="none" stroke="currentColor" stroke-width="1" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid-pattern)" />
      </svg>
    </div>

    <!-- Botão de Troca de Tema no Canto Superior Direito -->
    <div class="absolute top-6 right-6 z-20">
      <button
        @click="toggleTheme"
        class="group relative flex items-center justify-center w-10 h-10 rounded-full border border-border/70 bg-surface-card/80 backdrop-blur-md text-text-muted hover:text-text-heading hover:border-primary-500/40 hover:bg-surface-hover transition-all duration-300 shadow-sm"
        :title="isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro'"
        aria-label="Alternar tema"
      >
        <Sun
          v-if="isDark"
          class="h-4 w-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300"
        />
        <Moon
          v-else
          class="h-4 w-4 text-primary-400 group-hover:-rotate-12 transition-transform duration-300"
        />
      </button>
    </div>

    <!-- Card Principal de Login -->
    <div class="relative z-10 w-full max-w-[440px] animate-fade-in">
      <div
        class="relative rounded-3xl border border-border/80 bg-surface-card/85 backdrop-blur-2xl p-7 sm:p-10 shadow-2xl shadow-black/20 ring-1 ring-white/10"
      >
        <!-- Topo: Marca e Identidade Visual -->
        <div class="flex flex-col items-center text-center mb-8">
          <!-- Ícone do Logo com Halo de Luz -->
          <div class="relative mb-5 flex items-center justify-center">
            <div
              class="absolute -inset-3 bg-gradient-to-tr from-primary-500/40 to-cyan-400/30 rounded-full blur-2xl animate-pulse pointer-events-none"
            ></div>
            <img
              src="/logo.png"
              alt="HydroTrack"
              class="relative h-24 w-24 sm:h-28 sm:w-28 object-contain drop-shadow-[0_10px_25px_rgba(14,165,233,0.35)] transition-transform duration-300 hover:scale-105"
            />
          </div>

          <h1 class="text-2xl font-bold tracking-tight text-text-heading flex items-center gap-1.5">
            HydroTrack
          </h1>

          <div
            class="inline-flex items-center gap-1.5 mt-2 px-3 py-1 rounded-full text-[11px] font-semibold tracking-wide uppercase bg-primary-500/10 border border-primary-500/20 text-primary-400"
          >
            <Droplets class="h-3 w-3" />
            Telemetria & Gestão Hídrica
          </div>
        </div>

        <!-- Banner de Erro -->
        <div
          v-if="error"
          class="mb-6 rounded-2xl bg-red-500/10 border border-red-500/25 p-4 text-xs font-medium text-red-400 flex items-start gap-3"
          style="white-space: pre-line"
        >
          <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
          <div class="leading-relaxed">{{ error }}</div>
        </div>

        <!-- Formulário de Autenticação -->
        <form @submit.prevent="handleLogin" novalidate class="space-y-4">
          <!-- Campo E-mail -->
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-text-body uppercase tracking-wider">
              E-mail de Acesso
            </label>
            <div class="relative">
              <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-text-muted" />
              <input
                v-model="email"
                type="email"
                placeholder="exemplo@hydrotrack.com"
                autocomplete="email"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm bg-surface border border-border text-text-heading placeholder:text-text-muted/70 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
              />
            </div>
          </div>

          <!-- Campo Senha -->
          <div class="space-y-1.5">
            <label class="block text-xs font-semibold text-text-body uppercase tracking-wider">
              Senha
            </label>
            <div class="relative">
              <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-text-muted" />
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full pl-10 pr-10 py-2.5 rounded-xl text-sm bg-surface border border-border text-text-heading placeholder:text-text-muted/70 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all font-mono"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-heading transition-colors"
                :title="showPassword ? 'Ocultar senha' : 'Exibir senha'"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Botão de Acesso Rápido de Demonstração (somente dev, se configurado) -->
          <div class="pt-1 flex items-center justify-between">
            <button
              v-if="showDemo"
              type="button"
              @click="fillDemoCredentials"
              :class="[
                'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full border transition-all duration-200 active:scale-95',
                demoFilled
                  ? 'bg-emerald-500/15 border-emerald-500/40 text-emerald-400'
                  : 'bg-surface hover:bg-surface-hover border-border/80 text-text-muted hover:text-text-heading',
              ]"
            >
              <Sparkles class="h-3 w-3 text-amber-400" />
              <span>{{ demoFilled ? 'Credenciais Inseridas!' : 'Preencher Demo Admin' }}</span>
            </button>

            <span class="text-[11px] text-text-muted font-mono">Bocaiúva-MG</span>
          </div>

          <!-- Botão Entrar -->
          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            :loading="loading"
            class="w-full justify-center !py-3 font-bold text-sm shadow-lg shadow-primary-600/25 mt-2"
          >
            <span>Acessar Plataforma</span>
            <ArrowRight class="h-4 w-4 ml-2" />
          </BaseButton>
        </form>

        <!-- Rodapé de Segurança e Compliance -->
        <div
          class="mt-8 pt-6 border-t border-border/40 flex flex-col items-center text-center gap-1.5"
        >
          <div class="inline-flex items-center gap-1.5 text-[11px] text-text-muted">
            <ShieldCheck class="h-3.5 w-3.5 text-emerald-400" />
            <span>Ambiente Seguro • Telemetria Criptografada</span>
          </div>
          <p class="text-[10px] text-text-muted/60">
            HydroTrack Telemetry Systems v1.0.0 • Todos os direitos reservados
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
