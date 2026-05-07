<script setup lang="ts">
import { useToastStore } from '@/stores/toast'
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const store = useToastStore()

const icons = {
  success: CheckCircle,
  error: XCircle,
  warning: AlertTriangle,
  info: Info,
}

const colors = {
  success:
    'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800',
  error:
    'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
  warning:
    'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800',
  info: 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
}
</script>

<template>
  <div class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
    <TransitionGroup
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-y-4 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform translate-y-2 opacity-0 scale-95"
    >
      <div
        v-for="toast in store.toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-center gap-3 w-80 px-4 py-3 rounded-xl border shadow-lg backdrop-blur-sm"
        :class="colors[toast.type]"
        role="alert"
      >
        <component :is="icons[toast.type]" class="w-5 h-5 shrink-0" />
        <p class="text-sm font-medium flex-1">{{ toast.message }}</p>
        <button
          @click="store.remove(toast.id)"
          class="shrink-0 p-1 rounded-md opacity-70 hover:opacity-100 transition-opacity hover:bg-black/5 dark:hover:bg-white/10"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
