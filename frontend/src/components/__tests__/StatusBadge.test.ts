import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusBadge from '../StatusBadge.vue'

/**
 * Testes do componente StatusBadge.
 *
 * Validam que o badge renderiza o texto correto e aplica as classes
 * CSS correspondentes ao status do hidrômetro.
 */

describe('StatusBadge', () => {
  it('renderiza badge verde para status online', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: 'online' },
    })

    expect(wrapper.text()).toContain('Online')
    expect(wrapper.html()).toContain('bg-green-500/15')
  })

  it('renderiza badge cinza para status offline', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: 'offline' },
    })

    expect(wrapper.text()).toContain('Offline')
    expect(wrapper.html()).toContain('bg-slate-500/15')
  })

  it('renderiza badge vermelho para status alert', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: 'alert' },
    })

    expect(wrapper.text()).toContain('Alerta')
    expect(wrapper.html()).toContain('bg-red-500/15')
  })
})
