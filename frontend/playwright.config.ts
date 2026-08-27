import { defineConfig, devices } from '@playwright/test'
import dotenv from 'dotenv'
import path from 'path'

/**
 * Le as variaveis de ambiente de .env.local e .env.e2e.
 */
dotenv.config({ path: path.resolve(import.meta.dirname, '.env.local') })
dotenv.config({ path: path.resolve(import.meta.dirname, '.env.e2e'), override: true })

/**
 * Configuracao dos testes end-to-end do HydroTrack.
 *
 * A suite cobre o fluxo critico: login, criacao de hidrometro,
 * ingestao M2M, geracao de alerta, exclusao e logout.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:4173',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  webServer: {
    command: 'npm run preview -- --port 4173',
    url: 'http://localhost:4173',
    reuseExistingServer: !process.env.CI,
    timeout: 120000,
  },
})
