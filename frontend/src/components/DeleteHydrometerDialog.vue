<script setup lang="ts">
import { ref } from 'vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import type { Hydrometer } from '@/types'

/**
 * Dialog de confirmacao de exclusao de hidrometro.
 *
 * @prop {Hydrometer} hydrometer - Hidrometro a ser excluido
 * @emits close - Emitido quando o dialog deve ser fechado
 */
const props = defineProps<{
  hydrometer: Hydrometer
}>()

const emit = defineEmits(['close'])

const store = useHydrometerStore()
const loading = ref(false)

async function confirmDelete() {
  loading.value = true
  try {
    await store.deleteHydrometer(props.hydrometer.id)
    emit('close')
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <BaseModal :open="true" title="Confirmar Exclusao" size="sm" @close="$emit('close')">
    <div class="text-sm text-text-body space-y-3">
      <p>
        Tem certeza que deseja excluir o hidrometro
        <strong class="text-text-heading">{{ hydrometer.code }}</strong>
        ?
      </p>
      <p class="text-text-muted">
        Esta acao e irreversivel. Todas as leituras e alertas associados a este dispositivo tambem
        serao removidos.
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
