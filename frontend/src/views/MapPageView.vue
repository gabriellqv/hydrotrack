<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import MapView from '@/components/MapView.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { HYDROMETER_TYPE_LABELS } from '@/constants'
import type { Hydrometer } from '@/types'

/**
 * View dedicada do mapa.
 *
 * Utiliza o componente base MapView para exibir a malha de dispositivos,
 * adicionando um painel lateral dinâmico para exibição de detalhes de telemetria
 * quando um pino é clicado.
 *
 * Implementa polling a cada 5 segundos, pausado em abas ocultas, cancela
 * requisições pendentes via AbortController e suporta centralização em
 * hidrômetro a partir do query param `hydrometer_id`.
 */

const store = useDashboardStore()
const route = useRoute()
const selectedHydrometer = ref<Hydrometer | null>(null)
const activeFilter = ref<'all' | 'online' | 'offline' | 'alert'>('all')

const mapViewRef = ref<InstanceType<typeof MapView> | null>(null)

/**
 * Intervalo de polling ativo em milissegundos. Pausado automaticamente quando a aba fica oculta.
 */
const POLLING_INTERVAL = 5_000
let pollingTimer: ReturnType<typeof setInterval> | null = null
let abortController: AbortController | null = null
let focusTimeout: ReturnType<typeof setTimeout> | null = null

/**
 * Hidrômetros filtrados conforme o filtro ativo de status.
 */
const filteredHydrometers = computed(() => {
  if (activeFilter.value === 'all') return store.mapHydrometers
  return store.mapHydrometers.filter((h) => h.status === activeFilter.value)
})

/**
 * Contadores derivados da lista de hidrômetros exibidos, sem nova requisição.
 */
const filterCounts = computed(() => {
  const all = store.mapHydrometers.length
  return {
    all,
    online: store.mapHydrometers.filter((h) => h.status === 'online').length,
    offline: store.mapHydrometers.filter((h) => h.status === 'offline').length,
    alert: store.mapHydrometers.filter((h) => h.status === 'alert').length,
  }
})

/**
 * Cancela a requisição anterior e cria um novo `AbortController`.
 */
function createAbortController() {
  abortController?.abort()
  abortController = new AbortController()
  return abortController
}

/**
 * Atualiza os dados do mapa. Rejeições são ignoradas porque a store já exibe toasts de erro.
 */
async function refreshMap() {
  const controller = createAbortController()
  await store.fetchMap(controller.signal).catch(() => {
    // A store já exibe toasts de erro.
  })
}

await refreshMap()

/**
 * Centraliza o mapa no hidrômetro indicado pelo query param `hydrometer_id`.
 * Aguarda 500ms para garantir que a instância Leaflet tenha sido montada.
 */
function focusHydrometerFromQuery() {
  const targetId = Number(route.query.hydrometer_id)
  if (!targetId) return

  const target = store.mapHydrometers.find((h) => h.id === targetId)
  if (!target) return

  selectedHydrometer.value = target
  focusTimeout = setTimeout(() => {
    mapViewRef.value?.centerAndOpenPopup(target.id, target.latitude, target.longitude)
  }, 500)
}

onMounted(() => {
  pollingTimer = setInterval(refreshMap, POLLING_INTERVAL)

  focusHydrometerFromQuery()

  document.addEventListener('visibilitychange', handleVisibilityChange)
})

/**
 * Interrompe o polling periódico.
 */
function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

/**
 * Pausa o polling quando a aba fica oculta e retoma quando volta ao foco.
 */
function handleVisibilityChange() {
  if (document.hidden) {
    stopPolling()
  } else if (!pollingTimer) {
    pollingTimer = setInterval(refreshMap, POLLING_INTERVAL)
  }
}

onUnmounted(() => {
  stopPolling()
  abortController?.abort()
  if (focusTimeout) {
    clearTimeout(focusTimeout)
    focusTimeout = null
  }
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})

/**
 * Seleciona um hidrômetro ao clicar em seu marcador no mapa.
 *
 * @param hydrometer - Hidrômetro selecionado.
 */
function handleMarkerClick(hydrometer: Hydrometer) {
  selectedHydrometer.value = hydrometer
}
</script>

<template>
  <div
    class="animate-fade-in flex flex-col gap-4 min-h-[calc(100dvh-3.5rem-2rem)] lg:h-[calc(100dvh-4rem)] lg:overflow-hidden"
  >
    <div>
      <h1 class="text-2xl font-bold text-text-heading">Mapa</h1>
      <p class="text-sm text-text-muted mt-1">
        Distribuição geográfica dos hidrômetros em Bocaiúva-MG
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 flex-1 min-h-0">
      <div class="lg:col-span-3 flex flex-col gap-3 min-h-0">
        <div
          class="flex flex-wrap gap-3 bg-surface-card/60 backdrop-blur-xl rounded-xl px-4 py-3 border border-border ring-1 ring-white/5"
        >
          <button
            @click="activeFilter = 'all'"
            :class="[
              'px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200',
              activeFilter === 'all'
                ? 'bg-primary-600 text-white shadow-lg shadow-primary-900/20 border-transparent'
                : 'bg-surface-card border border-border text-text-muted hover:text-text-heading',
            ]"
          >
            Todos <span class="ml-1 opacity-60">({{ filterCounts.all }})</span>
          </button>
          <button
            @click="activeFilter = 'online'"
            :class="[
              'px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2',
              activeFilter === 'online'
                ? 'bg-green-600/20 border border-green-500/50 text-green-400 shadow-lg shadow-green-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-green-400 hover:border-green-500/30',
            ]"
          >
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Online
            <span class="opacity-60">({{ filterCounts.online }})</span>
          </button>
          <button
            @click="activeFilter = 'alert'"
            :class="[
              'px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2',
              activeFilter === 'alert'
                ? 'bg-red-600/20 border border-red-500/50 text-red-400 shadow-lg shadow-red-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-red-400 hover:border-red-500/30',
            ]"
          >
            <span
              class="w-2 h-2 rounded-full bg-red-500"
              :class="{ 'animate-pulse': filterCounts.alert > 0 }"
            ></span>
            Alertas <span class="opacity-60">({{ filterCounts.alert }})</span>
          </button>
          <button
            @click="activeFilter = 'offline'"
            :class="[
              'px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2',
              activeFilter === 'offline'
                ? 'bg-slate-700/50 border border-slate-500/50 text-slate-300 shadow-lg shadow-slate-900/10'
                : 'bg-surface-card border border-border text-text-muted hover:text-slate-300 hover:border-slate-500/30',
            ]"
          >
            <span class="w-2 h-2 rounded-full bg-slate-500"></span> Offline
            <span class="opacity-60">({{ filterCounts.offline }})</span>
          </button>
        </div>

        <BaseCard compact class="flex-1 min-h-[500px] lg:min-h-0 flex flex-col [&>*]:flex-1">
          <MapView
            ref="mapViewRef"
            :hydrometers="filteredHydrometers"
            @marker-click="handleMarkerClick"
          />
        </BaseCard>
      </div>

      <div>
        <BaseCard :title="!selectedHydrometer ? 'Selecione um pino' : undefined">
          <template v-if="selectedHydrometer">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-border/50">
              <h2 class="text-lg font-bold text-text-heading">
                <RouterLink
                  :to="{ name: 'hydrometer-detail', params: { id: selectedHydrometer.id } }"
                  class="hover:text-primary-400 transition-colors"
                >
                  {{ selectedHydrometer.code }}
                </RouterLink>
              </h2>
              <RouterLink
                :to="{ name: 'hydrometer-detail', params: { id: selectedHydrometer.id } }"
                class="text-xs text-primary-400 hover:text-primary-300 font-medium transition-colors"
              >
                Ver detalhes &rarr;
              </RouterLink>
            </div>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-text-muted">Status</span>
                <StatusBadge :status="selectedHydrometer.status" />
              </div>
              <div class="flex justify-between">
                <span class="text-text-muted">Endereço</span>
                <span class="text-text-body text-right">{{ selectedHydrometer.address }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-text-muted">Bairro</span>
                <span class="text-text-body">{{ selectedHydrometer.neighborhood }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-text-muted">Tipo</span>
                <span class="text-text-body">{{
                  HYDROMETER_TYPE_LABELS[selectedHydrometer.type]
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-text-muted">Coordenadas</span>
                <span class="text-text-muted font-mono text-xs">
                  {{ selectedHydrometer.latitude }}, {{ selectedHydrometer.longitude }}
                </span>
              </div>
            </div>
          </template>
          <p v-else class="text-sm text-text-muted text-center py-8">
            Clique em um pino no mapa para ver os detalhes.
          </p>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
