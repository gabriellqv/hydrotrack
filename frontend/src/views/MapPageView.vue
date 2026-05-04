<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import BaseCard from '@/components/ui/BaseCard.vue'
import MapView from '@/components/MapView.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import type { Hydrometer } from '@/types'
import { ref } from 'vue'

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
      <!-- Mapa -->
      <div class="lg:col-span-3">
        <BaseCard compact>
          <MapView :hydrometers="store.mapHydrometers" @marker-click="handleMarkerClick" />
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
