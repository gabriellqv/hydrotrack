import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import AlertItem from '../AlertItem.vue'
import type { Alert } from '@/types'

/**
 * Testes do componente AlertItem.
 *
 * Validam a renderização dos dados do alerta, o rótulo por tipologia
 * e a exposição da ação de resolução apenas para administradores.
 */

const baseAlert: Alert = {
  id: 1,
  hydrometer_id: 10,
  type: 'high_consumption',
  message: 'Consumo acima do esperado',
  resolved: false,
  resolved_at: null,
  created_at: new Date().toISOString(),
}

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'dashboard', component: { template: '<div />' } },
      {
        path: '/hydrometers/:id',
        name: 'hydrometer-detail',
        component: { template: '<div />' },
      },
    ],
  })
}

function mountAlert(alert: Alert) {
  const router = makeRouter()
  return mount(AlertItem, {
    props: { alert },
    global: { plugins: [router] },
  })
}

describe('AlertItem', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renderiza a mensagem e o rótulo da tipologia', () => {
    const wrapper = mountAlert(baseAlert)

    expect(wrapper.text()).toContain('Consumo acima do esperado')
    expect(wrapper.text()).toContain('Consumo Excessivo')
    expect(wrapper.text()).toContain('Pendente')
  })

  it('mapeia corretamente os rótulos por tipo', () => {
    const zero = mountAlert({ ...baseAlert, type: 'zero_reading' })
    expect(zero.text()).toContain('Leitura Zero')

    const offline = mountAlert({ ...baseAlert, type: 'offline' })
    expect(offline.text()).toContain('Sem Comunicação')
  })

  it('exibe o estado resolvido', () => {
    const wrapper = mountAlert({
      ...baseAlert,
      resolved: true,
      resolved_at: new Date().toISOString(),
    })

    expect(wrapper.text()).toContain('Resolvido')
    expect(wrapper.text()).toContain('Tratado em')
  })

  it('não expõe a ação de resolver para não administradores', () => {
    const wrapper = mountAlert(baseAlert)

    expect(wrapper.find('button').exists()).toBe(false)
  })
})
