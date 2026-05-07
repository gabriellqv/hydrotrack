/**
 * Representa um hidrômetro no sistema de monitoramento.
 *
 * @interface Hydrometer
 * @property {number} id - Identificador único do hidrômetro
 * @property {string} code - Código do dispositivo (ex: "HYD-001")
 * @property {'online' | 'offline' | 'alert'} status - Estado atual do dispositivo
 * @property {'residential' | 'commercial' | 'industrial'} type - Tipo do imóvel
 */
export interface Hydrometer {
  id: number
  code: string
  latitude: number
  longitude: number
  address: string
  neighborhood: string
  status: 'online' | 'offline' | 'alert'
  type: 'residential' | 'commercial' | 'industrial'
  last_reading_at: string | null
  created_at: string
  readings?: Reading[]
}

/**
 * Representa uma leitura de consumo enviada por um sensor.
 *
 * @interface Reading
 * @property {number} value_m3 - Consumo em metros cúbicos
 * @property {string} reading_at - Timestamp ISO da leitura
 */
export interface Reading {
  id: number
  hydrometer_id: number
  value_m3: number
  reading_at: string
}

/**
 * Representa um alerta gerado pelo sistema de monitoramento.
 *
 * @interface Alert
 * @property {'high_consumption' | 'zero_reading' | 'offline'} type - Categoria do alerta
 * @property {boolean} resolved - Se o alerta já foi tratado pelo operador
 */
export interface Alert {
  id: number
  hydrometer_id: number
  hydrometer?: Hydrometer
  type: 'high_consumption' | 'zero_reading' | 'offline'
  message: string
  resolved: boolean
  resolved_at: string | null
  created_at: string
}

/**
 * Dados resumidos para os cards do dashboard.
 */
export interface DashboardSummary {
  total_hydrometers: number
  online: number
  offline: number
  alert: number
  total_readings_today: number
  pending_alerts: number
}

/**
 * Ponto de dados para o gráfico de consumo.
 */
export interface ConsumptionPoint {
  date: string
  total_m3: number
}

/**
 * Dados do usuário autenticado.
 */
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'operator'
}

/**
 * Resposta paginada da API Laravel.
 */
export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
