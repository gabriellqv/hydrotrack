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
 * Configuracao visual dos status de hidrometro.
 */
export const HYDROMETER_STATUS_CONFIG: Record<
  Hydrometer['status'],
  { label: string; color: string; dotClass: string }
> = {
  online: { label: 'Online', color: '#22c55e', dotClass: 'bg-green-500' },
  offline: { label: 'Offline', color: '#94a3b8', dotClass: 'bg-slate-500' },
  alert: { label: 'Em Alerta', color: '#ef4444', dotClass: 'bg-red-500' },
}

/**
 * Mapeamento de status de hidrometro para labels em portugues.
 */
export const HYDROMETER_STATUS_LABELS: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.label,
  offline: HYDROMETER_STATUS_CONFIG.offline.label,
  alert: HYDROMETER_STATUS_CONFIG.alert.label,
}

/**
 * Mapeamento de status de hidrometro para cores hexadecimais.
 */
export const HYDROMETER_STATUS_COLORS: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.color,
  offline: HYDROMETER_STATUS_CONFIG.offline.color,
  alert: HYDROMETER_STATUS_CONFIG.alert.color,
}

/**
 * Mapeamento de status de hidrometro para classes de cor de ponto.
 */
export const HYDROMETER_STATUS_DOT_CLASSES: Record<Hydrometer['status'], string> = {
  online: HYDROMETER_STATUS_CONFIG.online.dotClass,
  offline: HYDROMETER_STATUS_CONFIG.offline.dotClass,
  alert: HYDROMETER_STATUS_CONFIG.alert.dotClass,
}
