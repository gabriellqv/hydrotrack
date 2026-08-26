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

  /** Número máximo de toasts visíveis simultaneamente */
  const MAX_VISIBLE_TOASTS = 5

  /**
   * Adiciona uma notificação à fila com remoção automática.
   * Limita a fila a `MAX_VISIBLE_TOASTS` itens, removendo o mais antigo quando necessário.
   * Gera um identificador aleatório para o toast.
   *
   * @param toast - Dados da notificação (sem id).
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
   * Remove uma notificação específica da fila pelo id.
   *
   * @param id - Identificador do toast a remover.
   */
  function remove(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  /**
   * Emite uma notificação de sucesso.
   *
   * @param message - Texto da mensagem.
   * @param duration - Duração em ms (opcional).
   */
  function success(message: string, duration?: number) {
    add({ message, type: 'success', duration })
  }

  /**
   * Emite uma notificação de erro.
   *
   * @param message - Texto da mensagem.
   * @param duration - Duração em ms (opcional).
   */
  function error(message: string, duration?: number) {
    add({ message, type: 'error', duration })
  }

  /**
   * Emite uma notificação de aviso.
   *
   * @param message - Texto da mensagem.
   * @param duration - Duração em ms (opcional).
   */
  function warning(message: string, duration?: number) {
    add({ message, type: 'warning', duration })
  }

  /**
   * Emite uma notificação informativa.
   *
   * @param message - Texto da mensagem.
   * @param duration - Duração em ms (opcional).
   */
  function info(message: string, duration?: number) {
    add({ message, type: 'info', duration })
  }

  return { toasts, add, remove, success, error, warning, info }
})
