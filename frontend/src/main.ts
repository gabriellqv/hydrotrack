/**
 * Ponto de entrada principal da aplicação Vue.
 *
 * Inicializa o app, registra os plugins globais (Pinia, Vue Router)
 * e monta a árvore de componentes no elemento `#app` do DOM.
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
