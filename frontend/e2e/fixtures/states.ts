/**
 * Configuracao e estados compartilhados para os testes E2E.
 */
import type { APIRequestContext } from '@playwright/test'

/**
 * Dados do administrador padrao de demonstracao.
 */
export const e2eConfig = {
  admin: {
    email: process.env.PLAYWRIGHT_ADMIN_EMAIL || 'admin@hydrotrack.com',
    password: process.env.PLAYWRIGHT_ADMIN_PASSWORD || 'admin123',
  },
  api: {
    baseUrl: process.env.PLAYWRIGHT_API_BASE_URL || 'http://localhost:8000/api',
    ingestApiKey: process.env.PLAYWRIGHT_INGEST_API_KEY || '',
  },
}

/**
 * Gera um codigo unico de hidrometro para o teste.
 */
export function createTestHydrometer() {
  const suffix = Math.random().toString(36).substring(2, 8).toUpperCase()
  return {
    code: `HYD-E2E-${suffix}`,
    latitude: -17.1085,
    longitude: -43.8143,
    address: 'Rua das Aguas, 100',
    neighborhood: 'Centro',
    type: 'residential' as const,
  }
}

/**
 * Cenarios de leitura para geracao de alertas.
 */
export const readingScenarios = {
  zero: { valueM3: 0 },
  high: { valueM3: 15 },
  normal: { valueM3: 1.5 },
}

/**
 * Retorna o timestamp ISO atual.
 */
export function getNowIso(): string {
  return new Date().toISOString()
}

/**
 * Envia uma leitura M2M via API.
 */
export async function sendIngestReading(
  apiContext: APIRequestContext,
  code: string,
  valueM3: number,
  apiKey: string = e2eConfig.api.ingestApiKey,
) {
  return apiContext.post('/ingest', {
    headers: { 'X-API-Key': apiKey },
    data: {
      hydrometer_code: code,
      value_m3: valueM3,
      reading_at: getNowIso(),
    },
  })
}
