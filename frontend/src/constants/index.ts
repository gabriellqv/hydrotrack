/**
 * Constantes compartilhadas do frontend HydroTrack.
 */

import type { Hydrometer } from '@/types'

/**
 * Opções de período disponíveis para filtros e gráficos de consumo.
 */
export const PERIOD_OPTIONS: { days: 7 | 30 | 90; label: string }[] = [
  { days: 7, label: '7d' },
  { days: 30, label: '30d' },
  { days: 90, label: '90d' },
]

/**
 * Rótulos para os tipos de imóvel.
 */
export const HYDROMETER_TYPE_LABELS: Record<Hydrometer['type'], string> = {
  residential: 'Residencial',
  commercial: 'Comercial',
  industrial: 'Industrial',
}

/**
 * Configuração visual dos status de hidrômetro.
 */
export const HYDROMETER_STATUS_CONFIG: Record<
  Hydrometer['status'],
  { label: string; color: string; dotClass: string }
> = {
  online: { label: 'Online', color: '#22c55e', dotClass: 'bg-green-500' },
  offline: { label: 'Offline', color: '#94a3b8', dotClass: 'bg-slate-500' },
  alert: { label: 'Em Alerta', color: '#ef4444', dotClass: 'bg-red-500' },
}

export const HYDROMETER_STATUS_LABELS: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.label,
  offline: HYDROMETER_STATUS_CONFIG.offline.label,
  alert: HYDROMETER_STATUS_CONFIG.alert.label,
}

export const HYDROMETER_STATUS_COLORS: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.color,
  offline: HYDROMETER_STATUS_CONFIG.offline.color,
  alert: HYDROMETER_STATUS_CONFIG.alert.color,
}

export const HYDROMETER_STATUS_DOT_CLASSES: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.dotClass,
  offline: HYDROMETER_STATUS_CONFIG.offline.dotClass,
  alert: HYDROMETER_STATUS_CONFIG.alert.dotClass,
}
