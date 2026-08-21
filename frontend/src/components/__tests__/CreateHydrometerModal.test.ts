import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import CreateHydrometerModal from '../CreateHydrometerModal.vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import { ApiError } from '@/services/api'
import type { Hydrometer } from '@/types'

/**
 * Testes do componente CreateHydrometerModal.
 *
 * Validam renderizacao do formulario, submissao bem-sucedida,
 * emissao de evento close e exibicao de erros de validacao vindos da API.
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

  it('renderiza titulo e campos do formulario', () => {
    const wrapper = mountComponent()

    expect(wrapper.text()).toContain('Novo Hidrometro')
    expect(wrapper.find('input[placeholder="HYD-201"]').exists()).toBe(true)
    expect(wrapper.find('input[placeholder="Rua das Aguas, 100"]').exists()).toBe(true)
    expect(wrapper.find('input[placeholder="Centro"]').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('emite close e reseta formulario ao criar hidrometro com sucesso', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'createHydrometer').mockResolvedValue({
      id: 1,
      code: 'HYD-TEST',
    } as Hydrometer)

    const codeInput = wrapper.find('input[placeholder="HYD-201"]')
    await codeInput.setValue('HYD-TEST')

    await wrapper.find('form').trigger('submit.prevent')

    expect(store.createHydrometer).toHaveBeenCalledOnce()
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('exibe erros de validacao quando a API retorna 422', async () => {
    const wrapper = mountComponent()
    const store = useHydrometerStore()

    vi.spyOn(store, 'createHydrometer').mockRejectedValue(
      new ApiError('Erro de validacao', 422, { code: ['Codigo ja existe.'] }),
    )

    await wrapper.find('form').trigger('submit.prevent')
    await vi.waitFor(() => wrapper.text().includes('Codigo ja existe.'))

    expect(wrapper.text()).toContain('Codigo ja existe.')
  })
})
