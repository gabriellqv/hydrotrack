<script setup lang="ts">
import { ref, watch } from 'vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { ApiError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import type { Hydrometer, UpdateHydrometerPayload } from '@/types'

/**
 * Modal de edição de hidrômetro.
 *
 * Sincroniza o formulário com a prop `hydrometer` via `watch`, valida coordenadas
 * como números finitos e exibe erros 422 nos campos correspondentes.
 *
 * @prop {Hydrometer} hydrometer - Hidrômetro a ser editado.
 * @emits close - Emitido quando o modal deve ser fechado.
 */
const props = defineProps<{
  hydrometer: Hydrometer
}>()

const emit = defineEmits(['close'])

const store = useHydrometerStore()
const toast = useToastStore()

const editForm = ref({
  code: props.hydrometer.code,
  latitude: String(props.hydrometer.latitude),
  longitude: String(props.hydrometer.longitude),
  address: props.hydrometer.address,
  neighborhood: props.hydrometer.neighborhood,
  type: props.hydrometer.type,
})

const editFormErrors = ref<Record<string, string>>({})
const loading = ref(false)

/**
 * Converte uma string de coordenada em número finito.
 *
 * @param value - Valor digitado no campo de coordenada.
 * @returns Número válido ou `null` quando vazio/inválido.
 */
function parseCoordinate(value: string): number | null {
  const trimmed = value.trim()
  if (trimmed === '') return null
  const parsed = Number(trimmed)
  return Number.isFinite(parsed) ? parsed : null
}

/**
 * Monta o payload de atualização a partir do formulário.
 *
 * @returns Payload válido ou `null` quando as coordenadas são inválidas.
 */
function buildPayload(): UpdateHydrometerPayload | null {
  const latitude = parseCoordinate(editForm.value.latitude)
  const longitude = parseCoordinate(editForm.value.longitude)

  if (latitude === null || longitude === null) {
    return null
  }

  return {
    code: editForm.value.code,
    latitude,
    longitude,
    address: editForm.value.address,
    neighborhood: editForm.value.neighborhood,
    type: editForm.value.type,
  }
}

/**
 * Reseta o formulário quando a prop `hydrometer` é atualizada (reabertura com outro item).
 */
watch(
  () => props.hydrometer,
  (h) => {
    editForm.value = {
      code: h.code,
      latitude: String(h.latitude),
      longitude: String(h.longitude),
      address: h.address,
      neighborhood: h.neighborhood,
      type: h.type,
    }
    editFormErrors.value = {}
  },
  { immediate: true },
)

/**
 * Submete o formulário de edição, tratando erros de validação da API.
 */
async function handleEdit() {
  editFormErrors.value = {}
  loading.value = true

  const payload = buildPayload()
  if (!payload) {
    toast.error('Latitude e longitude devem ser números válidos.')
    loading.value = false
    return
  }

  try {
    await store.updateHydrometer(props.hydrometer.id, payload)

    emit('close')
  } catch (error) {
    if (error instanceof ApiError && error.status === 422 && error.errors) {
      for (const [field, messages] of Object.entries(error.errors)) {
        editFormErrors.value[field] = messages[0] || 'Erro de validação'
      }
    } else {
      toast.error('Erro inesperado ao atualizar o hidrômetro. Tente novamente.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <BaseModal :open="true" title="Editar Hidrômetro" @close="$emit('close')">
    <form @submit.prevent="handleEdit" class="space-y-4">
      <BaseInput
        v-model="editForm.code"
        label="Código"
        placeholder="HYD-201"
        :error="editFormErrors.code"
      />

      <div class="grid grid-cols-2 gap-4">
        <BaseInput
          v-model="editForm.latitude"
          label="Latitude"
          type="number"
          step="any"
          placeholder="-17.1085"
          :error="editFormErrors.latitude"
        />
        <BaseInput
          v-model="editForm.longitude"
          label="Longitude"
          type="number"
          step="any"
          placeholder="-43.8143"
          :error="editFormErrors.longitude"
        />
      </div>

      <BaseInput
        v-model="editForm.address"
        label="Endereço"
        placeholder="Rua das Águas, 100"
        :error="editFormErrors.address"
      />
      <BaseInput
        v-model="editForm.neighborhood"
        label="Bairro"
        placeholder="Centro"
        :error="editFormErrors.neighborhood"
      />

      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-text-body">Tipo</label>
        <select
          v-model="editForm.type"
          :class="[
            'w-full rounded-lg border bg-surface-card px-4 py-2.5 text-sm text-text-heading focus:outline-none focus:ring-2',
            editFormErrors.type
              ? 'border-danger focus:ring-danger/50'
              : 'border-border focus:ring-primary-500/50',
          ]"
        >
          <option value="residential">Residencial</option>
          <option value="commercial">Comercial</option>
          <option value="industrial">Industrial</option>
        </select>
        <p v-if="editFormErrors.type" class="text-xs text-danger mt-1">{{ editFormErrors.type }}</p>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('close')">Cancelar</BaseButton>
      <BaseButton :loading="loading" @click="handleEdit">Salvar Alterações</BaseButton>
    </template>
  </BaseModal>
</template>
