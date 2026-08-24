import { defineStore } from 'pinia'
import { ref } from 'vue'

/** Variantes visuais disponíveis para as notificações flutuantes */
export type ToastType = 'success' | 'error' | 'warning' | 'info'

/**
 * Representa uma notificação flutuante individual na fila de exibição.
 *
 * @property {string} id - Identificador único gerado automaticamente
 * @property {string} message - Texto exibido ao usuário
 * @property {ToastType} type - Variante visual (cor e ícone)
 * @property {number} duration - Tempo em ms antes da remoção automática
 */
export interface Toast {
  id: string
  message: string
  type: ToastType
  duration?: number
}

/**
 * Store de notificações globais (Toasts).
 *
 * Gerencia a fila de mensagens flutuantes exibidas no canto inferior
 * direito da aplicação. Cada toast é removido automaticamente após
 * o tempo de duração configurado (padrão: 4 segundos).
 */
export const useToastStore = defineStore('toast', () => {
  /** Fila reativa de notificações ativas na tela */
  const toasts = ref<Toast[]>([])

  /** Numero maximo de toasts visiveis simultaneamente */
  const MAX_VISIBLE_TOASTS = 5

  /**
   * Adiciona uma notificação à fila com remoção automática.
   *
   * @param {Omit<Toast, 'id'>} toast - Dados da notificação (sem ID)
   */
  function add(toast: Omit<Toast, 'id'>) {
    const id = Math.random().toString(36).substring(2, 9)

    if (toasts.value.length >= MAX_VISIBLE_TOASTS) {
      toasts.value.shift()
    }

    toasts.value.push({ ...toast, id })

    const duration = toast.duration ?? 4000
    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
  }

  /**
   * Remove uma notificação específica da fila pelo ID.
   *
   * @param {string} id - Identificador do toast a remover
   */
  function remove(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  /**
   * Atalho para emitir uma notificação de sucesso (verde).
   *
   * @param {string} message - Texto da mensagem
   * @param {number} duration - Duração em ms (opcional)
   */
  function success(message: string, duration?: number) {
    add({ message, type: 'success', duration })
  }

  /**
   * Atalho para emitir uma notificação de erro (vermelho).
   *
   * @param {string} message - Texto da mensagem
   * @param {number} duration - Duração em ms (opcional)
   */
  function error(message: string, duration?: number) {
    add({ message, type: 'error', duration })
  }

  /**
   * Atalho para emitir uma notificação de aviso (amarelo).
   *
   * @param {string} message - Texto da mensagem
   * @param {number} duration - Duração em ms (opcional)
   */
  function warning(message: string, duration?: number) {
    add({ message, type: 'warning', duration })
  }

  /**
   * Atalho para emitir uma notificação informativa (azul).
   *
   * @param {string} message - Texto da mensagem
   * @param {number} duration - Duração em ms (opcional)
   */
  function info(message: string, duration?: number) {
    add({ message, type: 'info', duration })
  }

  return { toasts, add, remove, success, error, warning, info }
})
