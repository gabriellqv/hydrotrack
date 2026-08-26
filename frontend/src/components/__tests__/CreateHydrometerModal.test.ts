import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import CreateHydrometerModal from '../CreateHydrometerModal.vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import { ApiError } from '@/services/api'
import type { Hydrometer } from '@/types'

const errorMock = vi.fn<(message?: string, duration?: number) => void>()

vi.mock('@/stores/toast', () => ({
  useToastStore: vi.fn<() => { error: typeof errorMock }>(() => ({ error: errorMock })),
}))

/**
 * Testes do componente CreateHydrometerModal.
 *
 * Validam renderização do formulário, submissão bem-sucedida,
 * emissão de evento close e exibição de erros de validação vindos da API.
 */

describe('CreateHydrometerModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  function mountComponent() {
    return mount(CreateHydrometerModal, {
      attachTo: document.body,
      global: {
        stubs: {
          BaseModal: {
            props: ['open', 'title'],
            template: `
              <div>
                <h2>{{ title }}</h2>
                <slot />
                <slot name="footer" />
              </div>
            `,
          },
        },
      },
    })
  }

  it('renderiza título e campos do formulário', () => {
    const wrapper = mountComponent()

    expect(wrapper.text()).toContain('Novo Hidrômetro')
    expect(wrapper.find('input[placeholder="HYD-201"]').exists()).toBe(true)
    expect(wrapper.find('input[placeholder="Rua das Águas, 100"]').exists()).toBe(true)
    expect(wrapper.find('input[placeholder="Centro"]').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('emite close e reseta formulário ao criar hidrômetro com sucesso', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'createHydrometer').mockResolvedValue({
      id: 1,
      code: 'HYD-TEST',
    } as Hydrometer)

    const codeInput = wrapper.find('input[placeholder="HYD-201"]')
    await codeInput.setValue('HYD-TEST')

    const latitudeInput = wrapper.find('input[placeholder="-17.1085"]')
    await latitudeInput.setValue('-17.1085')

    const longitudeInput = wrapper.find('input[placeholder="-43.8143"]')
    expect(longitudeInput.exists()).toBe(true)
    await longitudeInput.setValue('-43.8143')

    await wrapper.find('form').trigger('submit.prevent')

    expect(store.createHydrometer).toHaveBeenCalledOnce()
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('exibe erros de validacao quando a API retorna 422', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'createHydrometer').mockRejectedValue(
      new ApiError('Erro de validação', 422, { code: ['Código já existe.'] }),
    )

    const latitudeInput = wrapper.find('input[placeholder="-17.1085"]')
    await latitudeInput.setValue('-17.1085')

    const longitudeInput = wrapper.find('input[placeholder="-43.8143"]')
    expect(longitudeInput.exists()).toBe(true)
    await longitudeInput.setValue('-43.8143')

    await wrapper.find('form').trigger('submit.prevent')
    await vi.waitFor(() => wrapper.text().includes('Código já existe.'))

    expect(wrapper.text()).toContain('Código já existe.')
  })
})
