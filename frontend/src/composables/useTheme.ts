import { ref } from 'vue'

/**
 * Composable para gerenciamento do tema claro/escuro.
 *
 * Persiste a escolha do usuário no localStorage e alterna as classes
 * 'dark'/'light' no elemento <html>: 'light' ativa as CSS variables do
 * tema claro e 'dark' habilita as variantes dark: do Tailwind. O tema
 * padrão do sistema é escuro (#111924).
 */
const isDark = ref(true)

/**
 * Inicializa o tema a partir do valor persistido no localStorage.
 * O tema padrão do sistema é escuro (#111924).
 * Deve ser chamado uma vez no onMounted do App.vue.
 */
function initTheme() {
  const saved = localStorage.getItem('theme')
  if (saved === 'light') {
    isDark.value = false
  } else {
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
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
    html.classList.add('light')
  }
}

export function useTheme() {
  return { isDark, toggleTheme, initTheme }
}
