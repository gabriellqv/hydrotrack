/**
 * Ponto de entrada da aplicação Vue.
 *
 * Monta a instância principal, registra o Pinia e o router, e aplica os estilos globais.
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
