<script setup lang="ts">
import { watch } from 'vue'

/**
 * Modal overlay com animação de entrada/saída.
 *
 * @prop {boolean} open - Controla a visibilidade do modal
 * @prop {string} title - Título do modal
 * @prop {'sm' | 'md' | 'lg'} size - Largura do modal
 * @emits close - Emitido ao clicar no backdrop ou no botão de fechar
 */
const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg'
  }>(),
  {
    size: 'md',
  },
)

const emit = defineEmits<{
  close: []
}>()

/** Bloqueia o scroll do body quando o modal está aberto */
watch(
  () => props.open,
  (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  },
)
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />

        <!-- Conteúdo do modal -->
        <div
          :class="[
            'relative rounded-xl border border-slate-700/50 bg-surface-card shadow-2xl',
            'animate-fade-in',
            {
              'w-full max-w-sm': size === 'sm',
              'w-full max-w-lg': size === 'md',
              'w-full max-w-2xl': size === 'lg',
            },
          ]"
        >
          <!-- Header -->
          <div
            v-if="title"
            class="flex items-center justify-between border-b border-slate-700/50 px-6 py-4"
          >
            <h2 class="text-lg font-semibold text-slate-100">{{ title }}</h2>
            <button
              @click="emit('close')"
              class="rounded-lg p-1 text-slate-400 hover:bg-surface-hover hover:text-white transition-colors"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6">
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="border-t border-slate-700/50 px-6 py-4 flex justify-end gap-3"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
