import { test, expect, type APIRequestContext } from '@playwright/test'
import { createTestHydrometer, e2eConfig, readingScenarios, sendIngestReading } from './fixtures/states'

test.describe.configure({ mode: 'serial' })

test.describe('Fluxo critico', () => {
  let apiContext: APIRequestContext
  let hydrometerCode: string
  const testHydrometer = createTestHydrometer()

  test.beforeAll(async ({ playwright }) => {
    apiContext = await playwright.request.newContext({
      baseURL: e2eConfig.api.baseUrl,
      extraHTTPHeaders: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    })
  })

  test.afterAll(async () => {
    if (apiContext) {
      await apiContext.dispose()
    }
  })

  test('login como administrador', async ({ page }) => {
    await page.goto('/login')

    await expect(page).toHaveTitle(/HydroTrack/i)
    await expect(page.getByRole('heading', { name: /Entrar na plataforma/i })).toBeVisible()

    await page.getByLabel(/E-mail/i).fill(e2eConfig.admin.email)
    await page.getByLabel(/Senha/i).fill(e2eConfig.admin.password)
    await page.getByRole('button', { name: /Entrar/i }).click()

    await expect(page).toHaveURL('/')
    await expect(page.getByRole('heading', { name: /Dashboard/i })).toBeVisible()
  })

  test('criar hidrometro', async ({ page }) => {
    await page.goto('/hydrometers')

    await page.getByRole('button', { name: /Novo Hidrometro/i }).click()
    await expect(page.getByRole('heading', { name: /Novo Hidrometro/i })).toBeVisible()

    await page.getByLabel(/Codigo/i).fill(testHydrometer.code)
    await page.getByLabel(/Latitude/i).fill(String(testHydrometer.latitude))
    await page.getByLabel(/Longitude/i).fill(String(testHydrometer.longitude))
    await page.getByLabel(/Endereco/i).fill(testHydrometer.address)
    await page.getByLabel(/Bairro/i).fill(testHydrometer.neighborhood)

    await page.getByRole('button', { name: /Criar Hidrometro/i }).click()

    await expect(page.getByRole('heading', { name: /Novo Hidrometro/i })).not.toBeVisible()
    await expect(page.getByText(testHydrometer.code)).toBeVisible()

    hydrometerCode = testHydrometer.code
  })

  test('enviar leitura zerada e gerar alerta', async ({ page }) => {
    if (!e2eConfig.api.ingestApiKey) {
      test.skip(true, 'PLAYWRIGHT_INGEST_API_KEY nao configurada; pulando ingestao M2M')
    }

    const response = await sendIngestReading(apiContext, hydrometerCode, readingScenarios.zero.valueM3)
    expect(response.ok()).toBeTruthy()

    await page.goto('/alerts')
    await expect(page.getByRole('heading', { name: /Alertas/i })).toBeVisible()

    await expect(
      page.getByRole('link', { name: new RegExp(hydrometerCode, 'i') }).first(),
    ).toBeVisible({ timeout: 10_000 })

    await page.goto('/hydrometers')
    await expect(
      page
        .getByRole('row')
        .filter({ hasText: new RegExp(hydrometerCode, 'i') })
        .locator('text=Em Alerta'),
    ).toBeVisible({ timeout: 10_000 })
  })

  test('excluir hidrometro', async ({ page }) => {
    await page.goto('/hydrometers')

    const row = page.getByRole('row').filter({ hasText: new RegExp(hydrometerCode, 'i') })
    await expect(row).toBeVisible()

    await row.getByRole('button', { name: /Excluir hidrometro/i }).click()
    await expect(page.getByRole('heading', { name: /Confirmar Exclusao/i })).toBeVisible()
    await page.getByRole('button', { name: /Excluir/i }).click()

    await expect(row).not.toBeVisible()
    await expect(page.getByText(hydrometerCode)).not.toBeVisible()
  })

  test('logout', async ({ page }) => {
    await page.goto('/')
    await page.getByRole('button', { name: /Sair/i }).click()

    await expect(page).toHaveURL('/login')
    await expect(page.getByRole('heading', { name: /Entrar na plataforma/i })).toBeVisible()
  })
})
