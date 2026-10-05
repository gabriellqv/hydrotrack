<script setup lang="ts">
import { onUnmounted, onActivated, onDeactivated, watch, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import MapView from '@/components/MapView.vue'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import HydrometerInspectionCard from '@/components/HydrometerInspectionCard.vue'
import { OPERATION_CITY, MAP_CENTER, MAP_DEFAULT_ZOOM, MAP_POLLING_INTERVAL } from '@/constants/app'
import type { Hydrometer } from '@/types'
import {
  Search,
  Crosshair,
  X,
  Radio,
  Layers,
  PanelRightClose,
  PanelRightOpen,
} from 'lucide-vue-next'

/**
 * View Dedicada do Mapa de Telemetria.
 *
 * Exibe a malha de dispositivos distribuídos geograficamente na cidade de operação.
 * Ocupa 100% da altura vertical disponível (até a borda inferior da tela),
 * com suporte a:
 * - Filtros rápidos com contadores animados por status
 * - Busca textual por código, endereço ou bairro
 * - Painel lateral retrátil de inspeção técnica
 * - Centralização instantânea na cidade
 * - Polling em tempo real a cada 5 segundos
 */

const store = useDashboardStore()
const route = useRoute()

const selectedHydrometer = ref<Hydrometer | null>(null)
const activeFilter = ref<'all' | 'online' | 'offline' | 'alert'>('all')
const searchQuery = ref('')
const showSidePanel = ref(true)

const mapViewRef = ref<InstanceType<typeof MapView> | null>(null)

/** Intervalo de polling em milissegundos (5s) */
const POLLING_INTERVAL = MAP_POLLING_INTERVAL
let pollingTimer: ReturnType<typeof setInterval> | null = null

const filterCounts = computed(() => ({
  all: store.mapHydrometers.length,
  online: store.mapHydrometers.filter((h) => h.status === 'online').length,
  offline: store.mapHydrometers.filter((h) => h.status === 'offline').length,
  alert: store.mapHydrometers.filter((h) => h.status === 'alert').length,
}))

const filteredHydrometers = computed(() => {
  return store.mapHydrometers.filter((h) => {
    const matchesFilter = activeFilter.value === 'all' || h.status === activeFilter.value
    if (!matchesFilter) return false

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase().trim()
    return (
      h.code.toLowerCase().includes(q) ||
      h.address.toLowerCase().includes(q) ||
      h.neighborhood.toLowerCase().includes(q)
    )
  })
})

function checkTargetHydrometer() {
  const targetId = Number(route.query.hydrometer_id)
  if (targetId) {
    const target = store.mapHydrometers.find((h) => h.id === targetId)
    if (target) {
      selectedHydrometer.value = target
      showSidePanel.value = true
      setTimeout(() => {
        mapViewRef.value?.centerAndOpenPopup(target.id, target.latitude, target.longitude)
      }, 500)
    }
  }
}

function loadMap() {
  return store
    .fetchMap()
    .catch(() => {
      // Erros de rede são tratados pelo interceptor da API.
    })
    .then(() => checkTargetHydrometer())
}

/**
 * Inicia o polling e a sincronização com a query string.
 * A view é mantida pelo KeepAlive, então o ciclo usa onActivated/onDeactivated.
 */
function startPolling() {
  stopPolling()
  loadMap()
  pollingTimer = setInterval(loadMap, POLLING_INTERVAL)
}

function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

onActivated(() => {
  startPolling()
  setTimeout(() => {
    mapViewRef.value?.invalidateSize()
  }, 50)
})

onDeactivated(() => {
  stopPolling()
})

watch(
  () => route.query.hydrometer_id,
  () => {
    checkTargetHydrometer()
  },
)

onUnmounted(() => {
  stopPolling()
})

function handleMarkerClick(hydrometer: Hydrometer) {
  selectedHydrometer.value = hydrometer
  showSidePanel.value = true
}

function handleResetCenter() {
  mapViewRef.value?.centerOn(MAP_CENTER.latitude, MAP_CENTER.longitude, MAP_DEFAULT_ZOOM)
}

function handleCenterOnSelected() {
  if (!selectedHydrometer.value) return
  mapViewRef.value?.centerOn(
    Number(selectedHydrometer.value.latitude),
    Number(selectedHydrometer.value.longitude),
    17,
  )
}
</script>

<template>
  <div
    class="animate-fade-in flex flex-col gap-3 flex-1 min-h-0 h-[calc(100dvh-10rem)] lg:h-[calc(100dvh-2rem)] lg:overflow-hidden"
  >
    <!-- Header e Controles Rápidos -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-xl sm:text-2xl font-bold text-text-heading tracking-tight">
            Mapa da Rede
          </h1>
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-500/10 border border-primary-500/20 text-primary-400"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-primary-400 animate-pulse"></span>
            <AnimatedCounter :value="store.mapHydrometers.length" /> dispositivos
          </span>
        </div>
        <p class="text-xs sm:text-sm text-text-muted mt-0.5">
          Distribuição geográfica e telemetria operacional • {{ OPERATION_CITY }}
        </p>
      </div>

      <!-- Barra de Ferramentas / Ações Rápidas -->
      <div class="flex items-center gap-2">
        <!-- Campo de Busca -->
        <div class="relative w-full sm:w-60">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-text-muted" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar código, rua..."
            class="w-full pl-9 pr-8 py-1.5 rounded-full text-xs bg-surface-card border border-border text-text-body placeholder:text-text-muted focus:outline-none focus:border-primary-500/50 focus:ring-2 focus:ring-primary-500/10 transition-all shadow-sm"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-heading transition-colors"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>

        <!-- Botão Centralizar Cidade -->
        <BaseButton
          variant="secondary"
          size="sm"
          @click="handleResetCenter"
          :title="`Centralizar mapa em ${OPERATION_CITY}`"
          class="shrink-0 text-xs"
        >
          <Crosshair class="h-3.5 w-3.5 mr-1 text-primary-400" />
          Centralizar
        </BaseButton>

        <!-- Botão Alternar Painel Lateral -->
        <BaseButton
          variant="secondary"
          size="sm"
          @click="showSidePanel = !showSidePanel"
          :title="showSidePanel ? 'Ocultar painel lateral' : 'Exibir painel lateral'"
          class="shrink-0 text-xs hidden lg:inline-flex cursor-pointer select-none"
        >
          <component
            :is="showSidePanel ? PanelRightClose : PanelRightOpen"
            class="h-3.5 w-3.5 mr-1 text-text-muted"
          />
          {{ showSidePanel ? 'Ocultar Painel' : 'Exibir Painel' }}
        </BaseButton>
      </div>
    </div>

    <!-- Barra de Filtros Pill Standard -->
    <div
      class="flex flex-wrap items-center justify-between gap-2.5 bg-surface-card/60 backdrop-blur-xl rounded-2xl px-3.5 py-2 border border-border shrink-0 shadow-sm"
    >
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-text-muted flex items-center gap-1.5 mr-1">
          <Layers class="h-3.5 w-3.5" /> Filtrar:
        </span>

        <!-- Todos -->
        <button
          @click="activeFilter = 'all'"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 active:scale-[0.98]',
            activeFilter === 'all'
              ? 'bg-primary-600 text-white shadow-md shadow-primary-900/20'
              : 'bg-surface-card border border-border text-text-muted hover:text-text-heading hover:border-border-hover',
          ]"
        >
          Todos
          <span class="ml-1 opacity-70">(<AnimatedCounter :value="filterCounts.all" />)</span>
        </button>

        <!-- Online -->
        <button
          @click="activeFilter = 'online'"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
            activeFilter === 'online'
              ? 'bg-emerald-600/20 border border-emerald-500/50 text-emerald-400 shadow-md shadow-emerald-900/10'
              : 'bg-surface-card border border-border text-text-muted hover:text-emerald-400 hover:border-emerald-500/30',
          ]"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          Online
          <span class="opacity-70">(<AnimatedCounter :value="filterCounts.online" />)</span>
        </button>

        <!-- Alertas -->
        <button
          @click="activeFilter = 'alert'"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
            activeFilter === 'alert'
              ? 'bg-red-600/20 border border-red-500/50 text-red-400 shadow-md shadow-red-900/10'
              : 'bg-surface-card border border-border text-text-muted hover:text-red-400 hover:border-red-500/30',
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full bg-red-500"
            :class="{ 'animate-pulse': filterCounts.alert > 0 }"
          ></span>
          Em Alerta
          <span class="opacity-70">(<AnimatedCounter :value="filterCounts.alert" />)</span>
        </button>

        <!-- Offline -->
        <button
          @click="activeFilter = 'offline'"
          :class="[
            'px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-1.5 active:scale-[0.98]',
            activeFilter === 'offline'
              ? 'bg-slate-700/50 border border-slate-500/50 text-slate-300 shadow-md shadow-slate-900/10'
              : 'bg-surface-card border border-border text-text-muted hover:text-slate-300 hover:border-slate-500/30',
          ]"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
          Offline
          <span class="opacity-70">(<AnimatedCounter :value="filterCounts.offline" />)</span>
        </button>
      </div>

      <!-- Resumo de visíveis -->
      <span class="text-xs text-text-muted hidden sm:inline">
        Exibindo <strong class="text-text-heading">{{ filteredHydrometers.length }}</strong> de
        {{ store.mapHydrometers.length }}
      </span>
    </div>

    <!-- Grid: Mapa Interativo + Painel Lateral de Inspeção -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 flex-1 min-h-0">
      <!-- Container do Mapa (ocupa 8/9 cols ou 12 cols se painel fechado) -->
      <div
        :class="[
          'flex flex-col min-h-0 h-full transition-all duration-300',
          showSidePanel ? 'lg:col-span-8 xl:col-span-9' : 'lg:col-span-12',
        ]"
      >
        <BaseCard
          compact
          class="flex-1 flex flex-col [&>*]:flex-1 relative overflow-hidden rounded-2xl border border-border shadow-lg h-full !p-0"
        >
          <MapView
            ref="mapViewRef"
            :hydrometers="filteredHydrometers"
            @marker-click="handleMarkerClick"
          />
        </BaseCard>
      </div>

      <!-- Painel Lateral Desktop (coluna fixa no grid de 12 colunas) -->
      <aside
        v-if="showSidePanel"
        class="hidden lg:flex lg:col-span-4 xl:col-span-3 flex-col min-h-0 h-full transition-all duration-300"
      >
        <!-- Estado: Hidrômetro Selecionado -->
        <HydrometerInspectionCard
          v-if="selectedHydrometer"
          :hydrometer="selectedHydrometer"
          @close="selectedHydrometer = null"
          @center="handleCenterOnSelected"
        />

        <!-- Estado: Nenhum Hidrômetro Selecionado -->
        <div
          v-else
          class="h-full flex flex-col justify-between rounded-2xl p-5 border bg-surface-card border-border shadow-2xl ring-1 ring-black/5 dark:ring-white/10 text-center transition-all duration-300"
        >
          <div class="flex flex-col items-center justify-center my-auto py-4">
            <!-- Ícone radar de pulso -->
            <div class="relative w-16 h-16 flex items-center justify-center mb-3">
              <span
                class="absolute inset-0 rounded-full bg-primary-500/10 animate-ping opacity-75"
              ></span>
              <div
                class="relative w-12 h-12 rounded-full bg-primary-500/15 border border-primary-500/30 flex items-center justify-center text-primary-400 shadow-[0_0_15px_rgba(56,189,248,0.25)]"
              >
                <Radio class="h-6 w-6" />
              </div>
            </div>

            <h3 class="text-base font-bold text-text-heading">Inspeção de Telemetria</h3>
            <p class="text-xs text-text-muted mt-2 max-w-[240px] leading-relaxed">
              Clique em qualquer marcador no mapa para inspecionar endereço, coordenadas e métricas
              em tempo real.
            </p>
          </div>

          <!-- Dica rápida de uso -->
          <div class="rounded-xl p-3.5 border bg-surface/40 border-border/60 text-left">
            <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider block mb-1">
              Dica Operacional
            </span>
            <p class="text-xs text-text-body leading-relaxed">
              Pontos <strong class="text-red-400">vermelhos piscantes</strong> indicam hidrômetros
              com consumo excessivo ou anomalias críticas.
            </p>
          </div>
        </div>
      </aside>

      <!-- Card Overlay Mobile (apenas em telas móveis quando um hidrômetro está selecionado) -->
      <div
        v-if="selectedHydrometer"
        class="lg:hidden fixed inset-x-3 bottom-20 z-50 pointer-events-auto max-h-[70vh] shadow-2xl transition-all duration-300"
      >
        <HydrometerInspectionCard
          :hydrometer="selectedHydrometer"
          @close="selectedHydrometer = null"
          @center="handleCenterOnSelected"
        />
      </div>
    </div>
  </div>
</template>
