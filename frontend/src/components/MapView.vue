<script setup lang="ts">
import { ref, onMounted, onUnmounted, onActivated, watch } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import type { Hydrometer } from '@/types'
import { MAP_CENTER, MAP_DEFAULT_ZOOM, MAP_FOCUS_ZOOM } from '@/constants/app'

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

const router = useRouter()

/** Referência ao container DOM do mapa */
const mapContainer = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let markersLayer: L.LayerGroup | null = null
const markersById = new Map<number, L.Marker>()

/** Centro da malha, convertido para a tupla esperada pelo Leaflet */
const BOCAIUVA_CENTER: L.LatLngTuple = [MAP_CENTER.latitude, MAP_CENTER.longitude]
const DEFAULT_ZOOM = MAP_DEFAULT_ZOOM

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
  markersById.clear()

  if (!Array.isArray(props.hydrometers)) return

  props.hydrometers.forEach((h) => {
    const marker = L.marker([Number(h.latitude), Number(h.longitude)], {
      icon: createIcon(getMarkerColor(h.status), h.status),
    })

    const statusMap: Record<string, string> = {
      online: 'Online',
      offline: 'Offline',
      alert: 'Em Alerta',
    }

    // Popup construído via DOM/textContent (e não innerHTML) para evitar
    // XSS a partir de campos controláveis (código, endereço, bairro).
    const popupContent = document.createElement('div')

    const linkEl = document.createElement('a')
    linkEl.href = '#'
    linkEl.className =
      'font-bold !text-primary-500 hover:!text-primary-400 hover:underline transition-colors block text-base mb-1'
    linkEl.textContent = h.code
    linkEl.addEventListener('click', (e) => {
      e.preventDefault()
      router.push({ name: 'hydrometer-detail', params: { id: h.id } })
    })
    popupContent.appendChild(linkEl)

    popupContent.appendChild(document.createTextNode(h.address))
    popupContent.appendChild(document.createElement('br'))

    const neighborhoodEl = document.createElement('em')
    neighborhoodEl.className = 'text-xs opacity-75'
    neighborhoodEl.textContent = h.neighborhood
    popupContent.appendChild(neighborhoodEl)
    popupContent.appendChild(document.createElement('br'))

    popupContent.appendChild(document.createTextNode('Status: '))
    const statusEl = document.createElement('strong')
    statusEl.style.color = getMarkerColor(h.status)
    statusEl.textContent = (statusMap[h.status] || h.status).toUpperCase()
    popupContent.appendChild(statusEl)

    marker.bindPopup(popupContent)

    marker.on('click', () => emit('marker-click', h))
    markersLayer!.addLayer(marker)
    markersById.set(h.id, marker)
  })
}

onMounted(() => {
  if (!mapContainer.value) return

  map = L.map(mapContainer.value, {
    center: BOCAIUVA_CENTER,
    zoom: DEFAULT_ZOOM,
    minZoom: 13, // Não permite afastar quase nada
    maxZoom: 18, // Limite de aproximação
    maxBounds: [
      [-17.15, -43.86], // Sudoeste (Extremo da mancha urbana)
      [-17.06, -43.77], // Nordeste (Extremo da mancha urbana)
    ], // Caixa super restrita englobando os pinos gerados pela Factory
    maxBoundsViscosity: 1.0, // Impede "rebote" para fora da área permitida
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    noWrap: true,
  }).addTo(map)

  markersLayer = L.layerGroup().addTo(map)
  renderMarkers()

  let resizeObserver: ResizeObserver | null = null
  let resizeTimeout: ReturnType<typeof setTimeout> | null = null

  if (mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      if (resizeTimeout) clearTimeout(resizeTimeout)
      resizeTimeout = setTimeout(() => {
        requestAnimationFrame(() => {
          if (map) {
            map.invalidateSize()
            // As vezes Leaflet esconde markers em bounds incorretos durante o resize
            renderMarkers()
          }
        })
      }, 100)
    })
    resizeObserver.observe(mapContainer.value)
  }

  // Garantir que o mapa seja dimensionado corretamente após a primeira renderização
  setTimeout(() => {
    if (map) {
      map.invalidateSize()
      renderMarkers() // Força o re-desenho após o Leaflet calcular a área visível correta
    }
  }, 200)

  onUnmounted(() => {
    if (resizeTimeout) clearTimeout(resizeTimeout)
    resizeObserver?.disconnect()
    map?.remove()
    map = null
    markersLayer = null
  })
})

watch(() => props.hydrometers, renderMarkers)

/**
 * Permite que componentes pais centralizem o mapa em coordenadas específicas.
 */
function centerOn(lat: number, lng: number, zoomLevel = MAP_FOCUS_ZOOM) {
  if (map) {
    map.flyTo([lat, lng], zoomLevel, { duration: 1.5 })
  }
}

/**
 * Voa até o hidrômetro e abre automaticamente seu popup de detalhes após a viagem.
 */
function centerAndOpenPopup(id: number, lat: number, lng: number, zoomLevel = MAP_FOCUS_ZOOM) {
  if (map) {
    map.flyTo([lat, lng], zoomLevel, { duration: 1.5 })
    map.once('moveend', () => {
      const marker = markersById.get(id)
      if (marker) {
        marker.openPopup()
      }
    })
  }
}

function invalidateSize() {
  if (map) {
    map.invalidateSize()
  }
}

onActivated(() => {
  if (map) {
    setTimeout(() => {
      map?.invalidateSize()
    }, 50)
  }
})

defineExpose({
  centerOn,
  centerAndOpenPopup,
  invalidateSize,
})
</script>

<template>
  <div
    ref="mapContainer"
    class="relative z-0 isolate w-full h-full min-h-0 rounded-xl overflow-hidden shadow-lg"
  />
</template>
