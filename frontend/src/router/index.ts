/**
 * Configuração do Vue Router da aplicação.
 *
 * Define o mapeamento entre URLs e componentes de página.
 * Utiliza History API (URLs limpas, sem hash) para navegação SPA.
 */

import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [],
})

export default router
