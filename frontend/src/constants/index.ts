/**
 * Constantes compartilhadas do frontend HydroTrack.
 */

import type { Hydrometer } from '@/types'

/**
 * Periodos disponiveis para graficos de consumo.
 */
export const PERIOD_OPTIONS: { days: 7 | 30 | 90; label: string }[] = [
  { days: 7, label: '7d' },
  { days: 30, label: '30d' },
  { days: 90, label: '90d' },
]

/**
 * Mapeamento de tipos de imovel para labels em portugues.
 */
export const HYDROMETER_TYPE_LABELS: Record<Hydrometer['type'], string> = {
  residential: 'Residencial',
  commercial: 'Comercial',
  industrial: 'Industrial',
}

/**
 * Mapeamento de status de hidrometro para labels em portugues.
 */
export const HYDROMETER_STATUS_LABELS: Record<Hydrometer['status'], string> = {
  online: 'Online',
  offline: 'Offline',
  alert: 'Em Alerta',
}
