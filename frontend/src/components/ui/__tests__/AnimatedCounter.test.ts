import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AnimatedCounter from '../AnimatedCounter.vue'

/**
 * Testes do componente AnimatedCounter.
 *
 * Validam a formatação final, o tratamento de valores não numéricos
 * e a conclusão da animação de contagem.
 */

describe('AnimatedCounter', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('exibe o valor final formatado ao término da animação', async () => {
    const wrapper = mount(AnimatedCounter, {
      props: { value: 1234, duration: 100 },
    })

    vi.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toBe((1234).toLocaleString('pt-BR'))
  })

  it('exibe travessão para valores não numéricos', async () => {
    const wrapper = mount(AnimatedCounter, {
      props: { value: null },
    })

    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toBe('—')
  })

  it('preserva strings não numéricas sem animação', async () => {
    const wrapper = mount(AnimatedCounter, {
      props: { value: 'N/A' },
    })

    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toBe('N/A')
  })

  it('aplica o formatter customizado', async () => {
    const wrapper = mount(AnimatedCounter, {
      props: { value: 80, duration: 100, formatter: (v: number) => `${Math.round(v)}%` },
    })

    vi.advanceTimersByTime(300)
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toBe('80%')
  })
})
