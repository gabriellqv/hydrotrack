import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import DeleteHydrometerDialog from '../DeleteHydrometerDialog.vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import type { Hydrometer } from '@/types'

const errorMock = vi.fn<(message?: string, duration?: number) => void>()

vi.mock('@/stores/toast', () => ({
  useToastStore: vi.fn<() => { error: typeof errorMock }>(() => ({ error: errorMock })),
}))

/**
 * Testes do componente DeleteHydrometerDialog.
 *
 * Validam renderizacao da mensagem de confirmacao, chamada da store
 * de exclusao e emissao do evento close.
 */

const mockHydrometer: Hydrometer = {
  id: 3,
  code: 'HYD-003',
  latitude: -17.2,
  longitude: -43.9,
  address: 'Av. Principal',
  neighborhood: 'Bairro Novo',
  status: 'offline',
  type: 'commercial',
  last_reading_at: null,
  created_at: '2024-02-01T00:00:00Z',
}

describe('DeleteHydrometerDialog', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  function mountComponent() {
    return mount(DeleteHydrometerDialog, {
      props: { hydrometer: mockHydrometer },
      attachTo: document.body,
      global: {
        stubs: {
          BaseModal: {
            props: ['open', 'title', 'size'],
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

  it('renderiza mensagem de confirmacao com codigo do hidrometro', () => {
    const wrapper = mountComponent()

    expect(wrapper.text()).toContain('Confirmar Exclusao')
    expect(wrapper.text()).toContain('HYD-003')
    expect(wrapper.text()).toContain('Esta acao e irreversivel')
  })

  it('chama deleteHydrometer e emite close ao confirmar exclusao', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'deleteHydrometer').mockResolvedValue(undefined)

    const buttons = wrapper.findAll('button')
    const deleteButton = buttons.find((b) => b.text().includes('Excluir'))

    expect(deleteButton).toBeDefined()
    await deleteButton!.trigger('click')

    expect(store.deleteHydrometer).toHaveBeenCalledExactlyOnceWith(3)
    expect(wrapper.emitted('close')).toHaveLength(1)
  })
})
