import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

/**
 * Testes do guard de rotas do Vue Router.
 *
 * Validam redirecionamento para login em rotas protegidas e
 * redirecionamento para dashboard quando usuário autenticado tenta acessar login.
 */
const defineRoutes = () => [
  {
    path: '/login',
    name: 'login',
    component: { template: '<div />' },
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    name: 'dashboard',
    component: { template: '<div />' },
    meta: { requiresAuth: true },
  },
  {
    path: '/alerts',
    name: 'alerts',
    component: { template: '<div />' },
    meta: { requiresAuth: true },
  },
]

describe('router guard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('redireciona para login quando usuário não autenticado acessa rota protegida', async () => {
    const router = createRouter({
      history: createWebHistory(),
      routes: defineRoutes(),
    })

    // simula guard do router/index.ts
    router.beforeEach((to) => {
      const authStore = useAuthStore()
      if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } }
      }
    })

    await router.push('/alerts')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/alerts')
  })

  it('permite acesso a rota protegida quando usuário autenticado', async () => {
    const authStore = useAuthStore()
    authStore.token = 'fake-token'

    const router = createRouter({
      history: createWebHistory(),
      routes: defineRoutes(),
    })

    router.beforeEach((to) => {
      if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } }
      }
    })

    await router.push('/alerts')

    expect(router.currentRoute.value.name).toBe('alerts')
  })

  it('redireciona usuário autenticado que tenta acessar login para dashboard', async () => {
    const authStore = useAuthStore()
    authStore.token = 'fake-token'

    const router = createRouter({
      history: createWebHistory(),
      routes: defineRoutes(),
    })

    router.beforeEach((to) => {
      if (to.name === 'login' && authStore.isAuthenticated) {
        return { name: 'dashboard' }
      }
      if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } }
      }
    })

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('permite acesso a login quando usuário não autenticado', async () => {
    const router = createRouter({
      history: createWebHistory(),
      routes: defineRoutes(),
    })

    router.beforeEach((to) => {
      if (to.name === 'login' && useAuthStore().isAuthenticated) {
        return { name: 'dashboard' }
      }
      if (to.meta.requiresAuth && !useAuthStore().isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } }
      }
    })

    await router.push('/login')

    expect(router.currentRoute.value.name).toBe('login')
  })
})
