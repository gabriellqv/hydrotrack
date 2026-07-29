import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '../ui/BaseInput.vue'

/**
 * Testes do componente BaseInput.
 *
 * Validam renderizacao de label, erro, icone e atualizacao de v-model.
 */

describe('BaseInput', () => {
  it('renderiza label quando fornecido', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', label: 'Codigo' },
    })

    expect(wrapper.text()).toContain('Codigo')
  })

  it('renderiza mensagem de erro quando fornecida', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', error: 'Campo obrigatorio' },
    })

    expect(wrapper.text()).toContain('Campo obrigatorio')
    expect(wrapper.find('input').classes()).toContain('border-danger')
  })

  it('emite update:modelValue ao digitar', async () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '' },
    })

    await wrapper.find('input').setValue('HYD-001')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')![0]).toEqual(['HYD-001'])
  })

  it('renderiza slot de icone', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '' },
      slots: { icon: '<span data-testid="icon">Icon</span>' },
    })

    expect(wrapper.find('[data-testid="icon"]').exists()).toBe(true)
  })
})
