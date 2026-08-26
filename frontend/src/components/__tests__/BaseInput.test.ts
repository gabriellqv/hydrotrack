import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BaseInput from '../ui/BaseInput.vue'

/**
 * Testes do componente BaseInput.
 *
 * Validam renderização de label, erro, ícone e atualização de v-model.
 */

describe('BaseInput', () => {
  it('renderiza label quando fornecido', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', label: 'Código' },
    })

    expect(wrapper.text()).toContain('Código')
  })

  it('renderiza mensagem de erro quando fornecida', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '', error: 'Campo obrigatório' },
    })

    expect(wrapper.text()).toContain('Campo obrigatório')
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

  it('renderiza slot de ícone', () => {
    const wrapper = mount(BaseInput, {
      props: { modelValue: '' },
      slots: { icon: '<span data-testid="icon">Icon</span>' },
    })

    expect(wrapper.find('[data-testid="icon"]').exists()).toBe(true)
  })
})
