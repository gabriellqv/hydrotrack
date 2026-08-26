import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import EditHydrometerModal from '../EditHydrometerModal.vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import type { Hydrometer } from '@/types'

const errorMock = vi.fn<(message?: string, duration?: number) => void>()

vi.mock('@/stores/toast', () => ({
  useToastStore: vi.fn<() => { error: typeof errorMock }>(() => ({ error: errorMock })),
}))

/**
 * Testes do componente EditHydrometerModal.
 *
 * Validam preenchimento inicial do formulário com os dados do hidrômetro,
 * submissão bem-sucedida, chamada da store e emissão do evento close.
 */

const mockHydrometer: Hydrometer = {
  id: 2,
  code: 'HYD-002',
  latitude: -17.1085,
  longitude: -43.8143,
  address: 'Rua das Águas',
  neighborhood: 'Centro',
  status: 'online',
  type: 'residential',
  last_reading_at: null,
  created_at: '2024-01-01T00:00:00Z',
}

describe('EditHydrometerModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  function mountComponent() {
    return mount(EditHydrometerModal, {
      props: { hydrometer: mockHydrometer },
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

  it('renderiza título e preenche campos com dados do hidrômetro', () => {
    const wrapper = mountComponent()

    expect(wrapper.text()).toContain('Editar Hidrômetro')

    const codeInput = wrapper.find('input[placeholder="HYD-201"]')
    expect(codeInput.exists()).toBe(true)
    expect((codeInput.element as HTMLInputElement).value).toBe('HYD-002')

    const addressInput = wrapper.find('input[placeholder="Rua das Águas, 100"]')
    expect(addressInput.exists()).toBe(true)
    expect((addressInput.element as HTMLInputElement).value).toBe('Rua das Águas')

    const neighborhoodInput = wrapper.find('input[placeholder="Centro"]')
    expect(neighborhoodInput.exists()).toBe(true)
    expect((neighborhoodInput.element as HTMLInputElement).value).toBe('Centro')

    const select = wrapper.find('select')
    expect(select.exists()).toBe(true)
    expect((select.element as HTMLSelectElement).value).toBe('residential')
  })

  it('atualiza campo e chama store ao salvar alterações', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'updateHydrometer').mockResolvedValue(undefined)

    const codeInput = wrapper.find('input[placeholder="HYD-201"]')
    await codeInput.setValue('HYD-002-EDIT')

    await wrapper.find('form').trigger('submit.prevent')

    expect(store.updateHydrometer).toHaveBeenCalledExactlyOnceWith(2, {
      code: 'HYD-002-EDIT',
      latitude: -17.1085,
      longitude: -43.8143,
      address: 'Rua das Águas',
      neighborhood: 'Centro',
      type: 'residential',
    })

    expect(wrapper.emitted('close')).toHaveLength(1)
  })
})
