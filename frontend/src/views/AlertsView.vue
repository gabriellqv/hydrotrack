<script setup lang="ts">
import { ref, computed, onMounted, onActivated } from 'vue'
import { useAlertStore } from '@/stores/alert'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import AlertItem from '@/components/AlertItem.vue'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import {
  Bell,
  AlertTriangle,
  CheckCircle2,
  RefreshCw,
  Search,
  X,
  Flame,
  Droplets,
  WifiOff,
  Layers,
  ShieldCheck,
} from 'lucide-vue-next'

/**
 * View Central de Alertas e Anomalias.
 *
 * Gerencia incidentes detectados na rede de telemetria hídrica.
 * Fornece:
 * - KPIs de resolução e incidentes pendentes
 * - Filtros rápidos por tipologia e status
 * - Busca textual em tempo real por código ou mensagem
 * - Atualização manual instantânea com feedback visual
 */

const store = useAlertStore()
const searchQuery = ref('')
const isRefreshing = ref(false)

/**
 * Contadores dos KPIs vindos do agregado do backend (todo o histórico).
 * Não devem ser derivados de `store.alerts`, exceto como fallback gracioso caso
 * o endpoint de métricas agregadas esteja indisponível ou em transição.
 */
const totalCount = computed(() => store.stats?.total ?? store.alerts.length)
const pendingCount = computed(
  () => store.stats?.pending ?? store.alerts.filter((a) => !a.resolved).length,
)
const resolvedCount = computed(
  () => store.stats?.resolved ?? store.alerts.filter((a) => a.resolved).length,
)
const resolutionRate = computed(() => {
  if (store.stats) return store.stats.resolution_rate
  if (totalCount.value === 0) return 100
  return Math.round((resolvedCount.value / totalCount.value) * 100)
})

/** Alertas filtrados por busca textual no client */
const displayAlerts = computed(() => {
  if (!searchQuery.value.trim()) return store.alerts
  const q = searchQuery.value.toLowerCase().trim()
  return store.alerts.filter(
    (a) =>
      a.message.toLowerCase().includes(q) ||
      a.hydrometer?.code.toLowerCase().includes(q) ||
      a.hydrometer?.neighborhood?.toLowerCase().includes(q),
  )
})

/** Altera o filtro de tipo e recarrega os alertas */
async function filterByType(type: string) {
  store.filters.type = store.filters.type === type ? '' : type
  await store.fetchAlerts()
}

/** Altera o filtro de status e recarrega os alertas */
async function filterByResolved(resolved: string) {
  store.filters.resolved = store.filters.resolved === resolved ? '' : resolved
  await store.fetchAlerts()
}

async function handleRefresh() {
  isRefreshing.value = true
  try {
    await Promise.all([store.fetchAlerts(), store.fetchStats()])
  } finally {
    setTimeout(() => (isRefreshing.value = false), 600)
  }
}

function resetFilters() {
  searchQuery.value = ''
  store.filters.type = ''
  store.filters.resolved = ''
  store.fetchAlerts()
}

let isInitialMount = true

onMounted(() => {
  store.fetchAlerts()
  store.fetchStats().catch(() => {})
})

onActivated(() => {
  if (isInitialMount) {
    isInitialMount = false
    return
  }
  store.fetchAlerts()
  store.fetchStats().catch(() => {})
})
</script>

<template>
  <div class="animate-fade-in flex flex-col gap-6 pb-12">
    <!-- Header Principal -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-2xl font-bold text-text-heading tracking-tight">Central de Alertas</h1>
          <span
            v-if="pendingCount > 0"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/20 text-amber-400"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            <AnimatedCounter :value="pendingCount" /> pendentes
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400"
          >
            <CheckCircle2 class="h-3 w-3" />
            Normalizado
          </span>
        </div>
        <p class="text-sm text-text-muted mt-1">
          Monitoramento proativo de anomalias de consumo, vazamentos suspeitos e desconexões
        </p>
      </div>

      <!-- Ações do Header -->
      <div class="flex items-center gap-3">
        <BaseButton
          variant="secondary"
          size="sm"
          @click="handleRefresh"
          :disabled="isRefreshing || store.loading"
        >
          <RefreshCw
            class="h-3.5 w-3.5 mr-1.5 text-primary-400"
            :class="{ 'animate-spin': isRefreshing || store.loading }"
          />
          Atualizar Alertas
        </BaseButton>
      </div>
    </div>

    <!-- Cards de Resumo / KPI de Alertas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total de Incidentes -->
      <BaseCard class="!p-4 border border-border/80 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted uppercase tracking-wider"
            >Total Registrado</span
          >
          <div
            class="w-8 h-8 rounded-full bg-primary-500/10 border border-primary-500/20 flex items-center justify-center text-primary-400"
          >
            <Bell class="h-4 w-4" />
          </div>
        </div>
        <div class="mt-2 text-2xl font-bold text-text-heading font-mono">
          <AnimatedCounter :value="totalCount" />
        </div>
        <span class="text-[11px] text-text-muted mt-1 block">Histórico do período ativo</span>
      </BaseCard>

      <!-- Pendentes de Ação -->
      <BaseCard class="!p-4 border border-amber-500/20 bg-amber-500/[0.02] rounded-2xl shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider"
            >Pendentes</span
          >
          <div
            class="w-8 h-8 rounded-full bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400"
          >
            <AlertTriangle class="h-4 w-4" />
          </div>
        </div>
        <div class="mt-2 text-2xl font-bold text-amber-400 font-mono">
          <AnimatedCounter :value="pendingCount" />
        </div>
        <span class="text-[11px] text-amber-400/80 mt-1 block">Exigem intervenção técnica</span>
      </BaseCard>

      <!-- Resolvidos -->
      <BaseCard
        class="!p-4 border border-emerald-500/20 bg-emerald-500/[0.02] rounded-2xl shadow-sm"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider"
            >Resolvidos</span
          >
          <div
            class="w-8 h-8 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400"
          >
            <CheckCircle2 class="h-4 w-4" />
          </div>
        </div>
        <div class="mt-2 text-2xl font-bold text-emerald-400 font-mono">
          <AnimatedCounter :value="resolvedCount" />
        </div>
        <span class="text-[11px] text-emerald-400/80 mt-1 block">Tratados com sucesso</span>
      </BaseCard>

      <!-- Taxa de Resolução -->
      <BaseCard class="!p-4 border border-border/80 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted uppercase tracking-wider"
            >Taxa de Resolução</span
          >
          <div
            class="w-8 h-8 rounded-full bg-slate-500/10 border border-slate-500/20 flex items-center justify-center text-text-muted"
          >
            <ShieldCheck class="h-4 w-4" />
          </div>
        </div>
        <div class="mt-2 text-2xl font-bold text-text-heading font-mono flex items-baseline gap-1">
          <AnimatedCounter :value="resolutionRate" />%
        </div>
        <!-- Mini barra de progresso -->
        <div class="w-full bg-surface-hover h-1.5 rounded-full overflow-hidden mt-2">
          <div
            class="h-full bg-gradient-to-r from-primary-500 to-emerald-400 transition-all duration-500 rounded-full"
            :style="{ width: `${resolutionRate}%` }"
          ></div>
        </div>
      </BaseCard>
    </div>

    <!-- Barra de Filtros e Busca -->
    <BaseCard compact class="rounded-2xl border border-border shadow-sm">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-2">
        <!-- Filtros Pill -->
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold text-text-muted flex items-center gap-1 mr-1">
            <Layers class="h-3.5 w-3.5" /> Tipo:
          </span>

          <!-- Todos os tipos -->
          <button
            @click="filterByType('')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 active:scale-[0.98]',
              store.filters.type === ''
                ? 'bg-primary-600 text-white shadow-md shadow-primary-900/20'
                : 'bg-surface-card border border-border text-text-muted hover:text-text-heading hover:border-border-hover',
            ]"
          >
            Todos
          </button>

          <!-- Consumo Alto -->
          <button
            @click="filterByType('high_consumption')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
              store.filters.type === 'high_consumption'
                ? 'bg-red-600/20 border border-red-500/50 text-red-400 shadow-md shadow-red-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-red-400 hover:border-red-500/30',
            ]"
          >
            <Flame class="h-3 w-3" />
            Consumo Alto
          </button>

          <!-- Leitura Zero -->
          <button
            @click="filterByType('zero_reading')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
              store.filters.type === 'zero_reading'
                ? 'bg-amber-600/20 border border-amber-500/50 text-amber-400 shadow-md shadow-amber-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-amber-400 hover:border-amber-500/30',
            ]"
          >
            <Droplets class="h-3 w-3" />
            Leitura Zero
          </button>

          <!-- Sem Comunicação -->
          <button
            @click="filterByType('offline')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
              store.filters.type === 'offline'
                ? 'bg-slate-700/50 border border-slate-500/50 text-slate-300 shadow-md shadow-slate-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-slate-300 hover:border-slate-500/30',
            ]"
          >
            <WifiOff class="h-3 w-3" />
            Sem Comunicação
          </button>

          <div class="h-4 w-px bg-border/70 mx-1 hidden sm:block"></div>

          <!-- Status: Pendentes / Resolvidos -->
          <button
            @click="filterByResolved('false')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 active:scale-[0.98]',
              store.filters.resolved === 'false'
                ? 'bg-amber-600/20 border border-amber-500/50 text-amber-400 shadow-md shadow-amber-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-amber-400 hover:border-amber-500/30',
            ]"
          >
            Apenas Pendentes
          </button>

          <button
            @click="filterByResolved('true')"
            :class="[
              'px-3.5 py-1 rounded-full text-xs font-semibold transition-all duration-200 active:scale-[0.98]',
              store.filters.resolved === 'true'
                ? 'bg-emerald-600/20 border border-emerald-500/50 text-emerald-400 shadow-md shadow-emerald-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-emerald-400 hover:border-emerald-500/30',
            ]"
          >
            Resolvidos
          </button>
        </div>

        <!-- Campo de Busca Textual -->
        <div class="relative w-full lg:w-72">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-text-muted" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar por mensagem, código..."
            class="w-full pl-9 pr-8 py-1.5 rounded-full text-xs bg-surface border border-border text-text-body placeholder:text-text-muted focus:outline-none focus:border-primary-500/50 focus:ring-2 focus:ring-primary-500/10 transition-all shadow-sm"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-heading transition-colors"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    </BaseCard>

    <!-- Lista de Alertas -->
    <div class="space-y-3">
      <!-- Skeleton / Loading -->
      <div v-if="store.loading" class="space-y-3">
        <div
          v-for="i in 3"
          :key="i"
          class="h-24 rounded-2xl bg-surface-card/40 border border-border/40 animate-pulse"
        ></div>
      </div>

      <!-- Estado Vazio -->
      <BaseCard
        v-else-if="displayAlerts.length === 0"
        class="py-16 text-center rounded-2xl border border-border shadow-sm flex flex-col items-center justify-center"
      >
        <div
          class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-4"
        >
          <ShieldCheck class="h-8 w-8" />
        </div>
        <h3 class="text-base font-bold text-text-heading">Nenhum alerta encontrado</h3>
        <p class="text-xs text-text-muted mt-1 max-w-sm">
          {{
            searchQuery || store.filters.type || store.filters.resolved
              ? 'Tente ajustar os filtros ou o termo de busca para visualizar os registros.'
              : 'Excelente! Todos os incidentes foram tratados ou não há anomalias ativas na rede.'
          }}
        </p>
        <BaseButton
          v-if="searchQuery || store.filters.type || store.filters.resolved"
          variant="secondary"
          size="sm"
          class="mt-4"
          @click="resetFilters"
        >
          Limpar Filtros
        </BaseButton>
      </BaseCard>

      <!-- Lista Renderizada -->
      <div v-else class="space-y-3">
        <AlertItem
          v-for="alert in displayAlerts"
          :key="alert.id"
          :alert="alert"
          @resolve="store.resolveAlert"
        />
      </div>
    </div>
  </div>
</template>
