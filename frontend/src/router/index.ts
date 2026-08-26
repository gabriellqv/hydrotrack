/**
 * Configuração de rotas da aplicação.
 *
 * Rotas públicas definem `meta.requiresAuth = false`. Rotas protegidas exigem
 * autenticação e são interceptadas pelo guard global `beforeEach`.
 */

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresAuth: false },
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/hydrometers',
      name: 'hydrometers',
      component: () => import('@/views/HydrometersView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/hydrometers/:id',
      name: 'hydrometer-detail',
      component: () => import('@/views/HydrometerDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/map',
      name: 'map',
      component: () => import('@/views/MapPageView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/alerts',
      name: 'alerts',
      component: () => import('@/views/AlertsView.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

/**
 * Guard global de navegação.
 *
 * Redireciona usuários não autenticados para `/login` quando a rota exige autenticação,
 * preservando a rota de destino no parâmetro de query `redirect`.
 * Redireciona usuários já autenticados que acessam `/login` para o dashboard.
 */
router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }
})

export default router
