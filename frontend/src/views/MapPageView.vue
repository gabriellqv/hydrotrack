<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import MapView from '@/components/MapView.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import type { Hydrometer } from '@/types'

/**
 * View Dedicada do Mapa.
 *
 * Utiliza o componente base MapView para exibir a mancha de dispositivos,
 * adicionando um painel lateral dinâmico para exibição de detalhes de telemetria
 * quando um pino é clicado.
 */

const store = useDashboardStore()
const selectedHydrometer = ref<Hydrometer | null>(null)

const typeMap: Record<string, string> = {
  residential: 'Residencial',
  commercial: 'Comercial',
  industrial: 'Industrial',
}

onMounted(() => store.fetchMap())

function handleMarkerClick(hydrometer: Hydrometer) {
  selectedHydrometer.value = hydrometer
}
</script>

<template>
  <div class="animate-fade-in space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-white">Mapa</h1>
      <p class="text-sm text-slate-500 mt-1">
        Distribuição geográfica dos hidrômetros em Bocaiúva-MG
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Mapa e Filtros -->
      <div class="lg:col-span-3 space-y-4">
        <!-- Filtros Rápidos -->
        <div class="flex flex-wrap gap-3">
          <button 
            @click="activeFilter = 'all'"
            :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200', activeFilter === 'all' ? 'bg-primary-600 text-white shadow-lg shadow-primary-900/20 border-transparent' : 'bg-surface-card border border-slate-700/50 text-slate-400 hover:text-slate-200']"
          >
            Todos <span class="ml-1 opacity-60">({{ filterCounts.all }})</span>
          </button>
          <button 
            @click="activeFilter = 'online'"
            :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2', activeFilter === 'online' ? 'bg-green-600/20 border border-green-500/50 text-green-400 shadow-lg shadow-green-900/10' : 'bg-surface-card border border-slate-700/50 text-slate-400 hover:text-green-400 hover:border-green-500/30']"
          >
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Online <span class="opacity-60">({{ filterCounts.online }})</span>
          </button>
          <button 
            @click="activeFilter = 'alert'"
            :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2', activeFilter === 'alert' ? 'bg-red-600/20 border border-red-500/50 text-red-400 shadow-lg shadow-red-900/10' : 'bg-surface-card border border-slate-700/50 text-slate-400 hover:text-red-400 hover:border-red-500/30']"
          >
            <span class="w-2 h-2 rounded-full bg-red-500" :class="{ 'animate-pulse': filterCounts.alert > 0 }"></span> Alertas <span class="opacity-60">({{ filterCounts.alert }})</span>
          </button>
          <button 
            @click="activeFilter = 'offline'"
            :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-200 flex items-center gap-2', activeFilter === 'offline' ? 'bg-slate-700/50 border border-slate-500/50 text-slate-300 shadow-lg shadow-slate-900/10' : 'bg-surface-card border border-slate-700/50 text-slate-400 hover:text-slate-300 hover:border-slate-500/30']"
          >
            <span class="w-2 h-2 rounded-full bg-slate-500"></span> Offline <span class="opacity-60">({{ filterCounts.offline }})</span>
          </button>
        </div>

        <BaseCard compact>
          <MapView :hydrometers="filteredHydrometers" @marker-click="handleMarkerClick" />
        </BaseCard>
      </div>

      <!-- Painel lateral -->
      <div>
        <BaseCard :title="selectedHydrometer ? selectedHydrometer.code : 'Selecione um pino'">
          <template v-if="selectedHydrometer">
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Status</span>
                <StatusBadge :status="selectedHydrometer.status" />
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Endereço</span>
                <span class="text-slate-300 text-right">{{ selectedHydrometer.address }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Bairro</span>
                <span class="text-slate-300">{{ selectedHydrometer.neighborhood }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Tipo</span>
                <span class="text-slate-300">{{ typeMap[selectedHydrometer.type] || selectedHydrometer.type }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Coordenadas</span>
                <span class="text-slate-400 font-mono text-xs">
                  {{ selectedHydrometer.latitude }}, {{ selectedHydrometer.longitude }}
                </span>
              </div>
            </div>
          </template>
          <p v-else class="text-sm text-slate-500 text-center py-8">
            Clique em um pino no mapa para ver os detalhes.
          </p>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
