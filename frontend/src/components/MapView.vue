<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
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
 * @param {string} status - Status do hidrômetro
 * @returns {L.DivIcon} Ícone Leaflet customizado
 */
function createIcon(color: string, status: string): L.DivIcon {
  const isAlert = status === 'alert'

  return L.divIcon({
    className: 'custom-marker bg-transparent border-0',
    html: `
      <div class="relative flex items-center justify-center" style="width: 16px; height: 16px;">
        ${isAlert ? `<span class="absolute inline-flex h-full w-full rounded-full opacity-50" style="background: ${color}; animation: soft-ping 2s ease-in-out infinite;"></span>` : ''}
        <span class="relative inline-flex rounded-full border-2 border-white" style="width: 14px; height: 14px; background: ${color}; box-shadow: 0 2px 4px rgba(0,0,0,0.4);"></span>
      </div>
    `,
    iconSize: [16, 16],
    iconAnchor: [8, 8],
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
      icon: createIcon(getMarkerColor(h.status), h.status),
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

  map = L.map(mapContainer.value, {
    center: BOCAIUVA_CENTER,
    zoom: DEFAULT_ZOOM,
    minZoom: 4, // Bloqueia zoom out excessivo
    maxBounds: [
      [-90, -180],
      [90, 180],
    ], // Trava o mapa dentro dos limites do mundo real
  })

  const lightMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    noWrap: true,
  })

  const darkMap = L.tileLayer(
    'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png',
    {
      attribution:
        '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a>',
      noWrap: true,
    },
  )

  const savedTheme = localStorage.getItem('mapTheme') || 'dark'

  if (savedTheme === 'light') {
    lightMap.addTo(map)
  } else {
    darkMap.addTo(map)
  }

  const baseMaps = {
    'Modo Escuro': darkMap,
    'Modo Claro': lightMap,
  }

  L.control.layers(baseMaps).addTo(map)

  map.on('baselayerchange', (e: L.LayersControlEvent) => {
    localStorage.setItem('mapTheme', e.name === 'Modo Claro' ? 'light' : 'dark')
  })

  markersLayer = L.layerGroup().addTo(map)
  renderMarkers()

  let resizeObserver: ResizeObserver | null = null

  if (mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      map?.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  }

  onUnmounted(() => {
    resizeObserver?.disconnect()
    map?.remove()
  })
})

watch(() => props.hydrometers, renderMarkers, { deep: true })
</script>

<template>
  <div
    ref="mapContainer"
    class="relative z-0 isolate w-full h-full min-h-0 rounded-xl overflow-hidden shadow-lg"
  />
</template>
