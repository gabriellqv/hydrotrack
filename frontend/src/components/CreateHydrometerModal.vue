<script setup lang="ts">
import { ref } from 'vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { ApiError } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import type { CreateHydrometerPayload } from '@/types'

/**
 * Modal de criacao de novo hidrometro.
 *
 * @emits close - Emitido quando o modal deve ser fechado
 */
const emit = defineEmits(['close'])

const store = useHydrometerStore()
const toast = useToastStore()

const form = ref({
  code: '',
  latitude: '',
  longitude: '',
  address: '',
  neighborhood: '',
  type: 'residential' as const,
})

const formErrors = ref<Record<string, string>>({})
const loading = ref(false)

function parseCoordinate(value: string): number | null {
  const trimmed = value.trim()
  if (trimmed === '') return null
  const parsed = Number(trimmed)
  return Number.isFinite(parsed) ? parsed : null
}

function buildPayload(): CreateHydrometerPayload | null {
  const latitude = parseCoordinate(form.value.latitude)
  const longitude = parseCoordinate(form.value.longitude)

  if (latitude === null || longitude === null) {
    return null
  }

  return {
    code: form.value.code,
    latitude,
    longitude,
    address: form.value.address,
    neighborhood: form.value.neighborhood,
    type: form.value.type,
  }
}

async function handleCreate() {
  formErrors.value = {}
  loading.value = true

  const payload = buildPayload()
  if (!payload) {
    toast.error('Latitude e longitude devem ser numeros validos.')
    loading.value = false
    return
  }

  try {
    await store.createHydrometer(payload)

    emit('close')
    resetForm()
  } catch (error) {
    if (error instanceof ApiError && error.status === 422 && error.errors) {
      for (const [field, messages] of Object.entries(error.errors)) {
        formErrors.value[field] = messages[0] || 'Erro de validacao'
      }
    } else {
      toast.error('Erro inesperado ao criar o hidrometro. Tente novamente.')
    }
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    code: '',
    latitude: '',
    longitude: '',
    address: '',
    neighborhood: '',
    type: 'residential',
  }
}
</script>

<template>
  <BaseModal :open="true" title="Novo Hidrometro" @close="$emit('close')">
    <form @submit.prevent="handleCreate" class="space-y-4">
      <BaseInput
        v-model="form.code"
        label="Codigo"
        placeholder="HYD-201"
        :error="formErrors.code"
      />

      <div class="grid grid-cols-2 gap-4">
        <BaseInput
          v-model="form.latitude"
          label="Latitude"
          type="number"
          step="any"
          placeholder="-17.1085"
          :error="formErrors.latitude"
        />
        <BaseInput
          v-model="form.longitude"
          label="Longitude"
          type="number"
          step="any"
          placeholder="-43.8143"
          :error="formErrors.longitude"
        />
      </div>

      <BaseInput
        v-model="form.address"
        label="Endereco"
        placeholder="Rua das Aguas, 100"
        :error="formErrors.address"
      />
      <BaseInput
        v-model="form.neighborhood"
        label="Bairro"
        placeholder="Centro"
        :error="formErrors.neighborhood"
      />

      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-text-body">Tipo</label>
        <select
          v-model="form.type"
          :class="[
            'w-full rounded-lg border bg-surface-card px-4 py-2.5 text-sm text-text-heading focus:outline-none focus:ring-2',
            formErrors.type
              ? 'border-danger focus:ring-danger/50'
              : 'border-border focus:ring-primary-500/50',
          ]"
        >
          <option value="residential">Residencial</option>
          <option value="commercial">Comercial</option>
          <option value="industrial">Industrial</option>
        </select>
        <p v-if="formErrors.type" class="text-xs text-danger mt-1">{{ formErrors.type }}</p>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('close')">Cancelar</BaseButton>
      <BaseButton :loading="loading" @click="handleCreate">Criar Hidrometro</BaseButton>
    </template>
  </BaseModal>
</template>
