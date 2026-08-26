import { ref } from 'vue'

/**
 * Composable para gerenciamento do tema claro/escuro.
 *
 * Persiste a escolha do usuário no `localStorage` e aplica/remove
 * a classe `light` no elemento `<html>` para ativar as CSS variables
 * do tema claro.
 */

/**
 * Estado reativo singleton do tema atual da aplicação.
 * `true` indica tema escuro; `false`, tema claro.
 */
const isDark = ref(false)

/**
 * Inicializa o tema a partir do valor persistido no `localStorage`.
 * Deve ser chamada uma vez durante a montagem da aplicação.
 */
function initTheme() {
  const saved = localStorage.getItem('theme')
  if (saved === 'dark') {
    isDark.value = true
  }
  applyTheme()
}

/**
 * Alterna entre tema claro e escuro e persiste a escolha no `localStorage`.
 */
function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  applyTheme()
}

/**
 * Aplica ou remove a classe `light` no elemento `<html>` conforme `isDark`.
 * Efeito colateral direto no DOM.
 */
function applyTheme() {
  const html = document.documentElement
  if (isDark.value) {
    html.classList.remove('light')
  } else {
    html.classList.add('light')
  }
}

/**
 * Expõe o estado e as ações de controle do tema.
 * Reutiliza as mesmas referências para todo o ciclo de vida da aplicação.
 *
 * @returns Estado reativo e funções para inicializar e alternar o tema.
 */
export function useTheme() {
  return { isDark, toggleTheme, initTheme }
}
