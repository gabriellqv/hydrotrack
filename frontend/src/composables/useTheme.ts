import { ref } from 'vue'

/**
 * Composable para gerenciamento do tema claro/escuro.
 *
 * Persiste a escolha do usuário no localStorage e aplica/remove
 * a classe 'light' no elemento <html> para ativar as CSS variables
 * do tema claro.
 */
const isDark = ref(false)

/**
 * Inicializa o tema a partir do valor persistido no localStorage.
 * Deve ser chamado uma vez no onMounted do App.vue.
 */
function initTheme() {
  const saved = localStorage.getItem('theme')
  if (saved === 'dark') {
    isDark.value = true
  }
  applyTheme()
}

/**
 * Alterna entre tema claro e escuro, persistindo a escolha.
 */
function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  applyTheme()
}

/**
 * Aplica ou remove a classe 'light' no elemento raiz do DOM.
 */
function applyTheme() {
  const html = document.documentElement
  if (isDark.value) {
    html.classList.remove('light')
  } else {
    html.classList.add('light')
  }
}

export function useTheme() {
  return { isDark, toggleTheme, initTheme }
}
