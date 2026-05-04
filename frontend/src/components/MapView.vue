<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Hydrometer } from '@/types'

/**
 * Componente de mapa interativo que renderiza hidrômetros como pinos coloridos.
 *
 * Utiliza Leaflet.js com tiles OpenStreetMap (gratuito, sem API key).
 * O mapa inicia centralizado em Bocaiúva-MG com zoom nível 14.
 *
 * @prop {Hydrometer[]} hydrometers - Lista de hidrômetros com coordenadas GPS
 * @emits marker-click - Emitido quando o usuário clica em um pino do mapa
 */

const props = defineProps<{
  hydrometers: Hydrometer[]
}>()

const emit = defineEmits<{
  'marker-click': [hydrometer: Hydrometer]
}>()

/** Referência ao container DOM do mapa */
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let markersLayer: L.LayerGroup | null = null

/** Centro de Bocaiúva-MG (Praça Wandick Dumont) */
const BOCAIUVA_CENTER: L.LatLngTuple = [-17.1085, -43.8143]
const DEFAULT_ZOOM = 14

/**
 * Retorna a cor do marcador com base no status do hidrômetro.
 *
 * @param {'online' | 'offline' | 'alert'} status - Status atual do dispositivo
 * @returns {string} Código hexadecimal da cor
 */
function getMarkerColor(status: string): string {
  const colors: Record<string, string> = {
    online: '#22c55e', // Verde — funcionando normalmente
    offline: '#94a3b8', // Cinza — sem comunicação
    alert: '#ef4444', // Vermelho — consumo anormal
  }
  return colors[status] || '#94a3b8'
}

/**
 * Cria um ícone SVG circular customizado para os marcadores do mapa.
 *
 * @param {string} color - Cor de preenchimento do ícone
 * @returns {L.DivIcon} Ícone Leaflet customizado
 */
function createIcon(color: string): L.DivIcon {
  return L.divIcon({
    className: 'custom-marker',
    html: `<div style="
      width: 14px; height: 14px;
      background: ${color};
      border: 2px solid white;
      border-radius: 50%;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    "></div>`,
    iconSize: [14, 14],
    iconAnchor: [7, 7],
  })
}

/**
 * Renderiza os marcadores no mapa com base na lista de hidrômetros.
 * Remove marcadores anteriores antes de adicionar os novos.
 */
function renderMarkers() {
  if (!map || !markersLayer) return

  markersLayer.clearLayers()

  props.hydrometers.forEach((h) => {
    const marker = L.marker([h.latitude, h.longitude], {
      icon: createIcon(getMarkerColor(h.status)),
    })

    const statusMap: Record<string, string> = {
      online: 'Online',
      offline: 'Offline',
      alert: 'Em Alerta',
    }

    marker.bindPopup(`
      <strong>${h.code}</strong><br>
      ${h.address}<br>
      <em>${h.neighborhood}</em><br>
      Status: <strong>${(statusMap[h.status] || h.status).toUpperCase()}</strong>
    `)

    marker.on('click', () => emit('marker-click', h))
    markersLayer!.addLayer(marker)
  })
}

onMounted(() => {
  if (!mapContainer.value) return

  map = L.map(mapContainer.value).setView(BOCAIUVA_CENTER, DEFAULT_ZOOM)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  markersLayer = L.layerGroup().addTo(map)
  renderMarkers()
})

watch(() => props.hydrometers, renderMarkers, { deep: true })
</script>

<template>
  <div ref="mapContainer" class="w-full h-[700px] rounded-xl overflow-hidden shadow-lg" />
</template>
