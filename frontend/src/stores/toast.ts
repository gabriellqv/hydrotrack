import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  message: string
  type: ToastType
  duration?: number
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])

  function add(toast: Omit<Toast, 'id'>) {
    const id = Math.random().toString(36).substring(2, 9)
    toasts.value.push({ ...toast, id })

    const duration = toast.duration ?? 4000
    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
  }

  function remove(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(message: string, duration?: number) {
    add({ message, type: 'success', duration })
  }

  function error(message: string, duration?: number) {
    add({ message, type: 'error', duration })
  }

  function warning(message: string, duration?: number) {
    add({ message, type: 'warning', duration })
  }

  function info(message: string, duration?: number) {
    add({ message, type: 'info', duration })
  }

  return { toasts, add, remove, success, error, warning, info }
})
