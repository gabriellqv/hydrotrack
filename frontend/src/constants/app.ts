/**
 * Constantes globais do HydroTrack.
 *
 * Centralizam dados de domínio (localização da malha e intervalos de
 * polling) que antes ficavam duplicados em diferentes componentes.
 */

/** Nome/cidade de operação da malha de telemetria */
export const OPERATION_CITY = 'Bocaiúva-MG'

/** Centro da malha urbana (Praça Wandick Dumont), usado como foco padrão do mapa */
export const MAP_CENTER = {
  latitude: -17.1085,
  longitude: -43.8143,
} as const

/** Zoom padrão do mapa e da centralização em um hidrômetro */
export const MAP_DEFAULT_ZOOM = 14
export const MAP_FOCUS_ZOOM = 17

/** Intervalos de polling (ms) por tela */
export const DASHBOARD_POLLING_INTERVAL = 15_000
export const MAP_POLLING_INTERVAL = 5_000
