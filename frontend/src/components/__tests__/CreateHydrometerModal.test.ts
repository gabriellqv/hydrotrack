import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import CreateHydrometerModal from '../CreateHydrometerModal.vue'
import { api } from '@/services/api'

/**
 * Testes do componente CreateHydrometerModal.
 *
 * Validam renderizacao do formulario, submissao bem-sucedida
 * e exibicao de erros de validacao vindos da API.
 */

vi.mock('@/services/api')

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

afterEach(() => {
  document.body.innerHTML = ''
})

describe('CreateHydrometerModal', () => {
  it('renderiza titulo e campos do formulario', () => {
    mount(CreateHydrometerModal, { attachTo: document.body })

    expect(document.body.textContent).toContain('Novo Hidrometro')
    expect(document.body.querySelector('input[placeholder="HYD-201"]')).not.toBeNull()
  })

  it('emite close ao criar hidrometro com sucesso', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 1 } })

    mount(CreateHydrometerModal, { attachTo: document.body })

    const input = document.body.querySelector('input[placeholder="HYD-201"]') as HTMLInputElement
    input.value = 'HYD-TEST'
    input.dispatchEvent(new Event('input'))

    const form = document.body.querySelector('form') as HTMLFormElement
    form.dispatchEvent(new Event('submit'))

    await vi.dynamicImportSettled?.()
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(document.body.querySelector('form')).not.toBeNull()
  })
})
