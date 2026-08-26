<script setup lang="ts">
import { ref } from 'vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { useToastStore } from '@/stores/toast'
import type { Hydrometer } from '@/types'

/**
 * Dialog de confirmação de exclusão de hidrômetro.
 *
 * @prop {Hydrometer} hydrometer - Hidrômetro a ser excluído.
 * @emits close - Emitido quando o dialog deve ser fechado.
 */
const props = defineProps<{
  hydrometer: Hydrometer
}>()

const emit = defineEmits(['close'])

const store = useHydrometerStore()
const toast = useToastStore()
const loading = ref(false)

/**
 * Confirma a exclusão do hidrômetro e fecha o dialog em caso de sucesso.
 */
async function confirmDelete() {
  loading.value = true
  try {
    await store.deleteHydrometer(props.hydrometer.id)
    emit('close')
  } catch {
    toast.error('Erro inesperado ao excluir o hidrômetro. Tente novamente.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <BaseModal :open="true" title="Confirmar Exclusão" size="sm" @close="$emit('close')">
    <div class="text-sm text-text-body space-y-3">
      <p>
        Tem certeza que deseja excluir o hidrômetro
        <strong class="text-text-heading">{{ hydrometer.code }}</strong>
        ?
      </p>
      <p class="text-text-muted">
        Esta ação é irreversível. Todas as leituras e alertas associados a este dispositivo também
        serão removidos.
      </p>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('close')">Cancelar</BaseButton>
      <BaseButton
        @click="confirmDelete"
        :loading="loading"
        class="!bg-red-600 hover:!bg-red-700 !border-red-600"
      >
        Excluir
      </BaseButton>
    </template>
  </BaseModal>
</template>
