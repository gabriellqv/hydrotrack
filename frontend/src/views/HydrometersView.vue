<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useHydrometerStore } from '@/stores/hydrometer'
import { useIsAdmin } from '@/composables/useIsAdmin'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import type { Hydrometer } from '@/types'
import { Plus, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { ApiError } from '@/services/api'

/**
 * View de Gerenciamento de Hidrômetros (CRUD).
 *
 * Exibe a tabela paginada com buscas e filtros integrados via HydrometerStore.
 * Controla os privilégios de acesso: apenas usuários com role 'admin'
 * têm permissão para criar, editar ou excluir hidrômetros.
 */

const store = useHydrometerStore()
const { isAdmin } = useIsAdmin()

const search = ref('')
const statusFilter = ref('')
const showCreateModal = ref(false)

const typeMap: Record<string, string> = {
  residential: 'Residencial',
  commercial: 'Comercial',
  industrial: 'Industrial',
}

/** Dados do formulário de criação */
const form = ref({
  code: '',
  latitude: '',
  longitude: '',
  address: '',
  neighborhood: '',
  type: 'residential' as const,
})

const formErrors = ref<Record<string, string>>({})

onMounted(() => store.fetchHydrometers())

function applyFilters() {
  const filters: Record<string, string> = {}
  if (statusFilter.value) filters.status = statusFilter.value
  if (search.value) filters.search = search.value
  store.fetchHydrometers(1, filters)
}

async function handleCreate() {
  formErrors.value = {}
  try {
    await store.createHydrometer({
      ...form.value,
      latitude: form.value.latitude === '' ? '' : Number(form.value.latitude),
      longitude: form.value.longitude === '' ? '' : Number(form.value.longitude),
    } as unknown as Omit<Hydrometer, 'id' | 'created_at' | 'status' | 'last_reading_at'>)
    showCreateModal.value = false
    form.value = { code: '', latitude: '', longitude: '', address: '', neighborhood: '', type: 'residential' }
  } catch (error) {
    if (error instanceof ApiError && error.status === 422 && error.errors) {
      for (const [field, messages] of Object.entries(error.errors)) {
        formErrors.value[field] = messages[0]
      }
    } else {
      console.error(error)
    }
  }
}
</script>

<template>
  <div class="animate-fade-in space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Hidrômetros</h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ store.pagination.total }} dispositivos cadastrados
        </p>
      </div>
      <BaseButton v-if="isAdmin" @click="showCreateModal = true">
        <Plus class="h-4 w-4" /> Novo Hidrômetro
      </BaseButton>
    </div>

    <!-- Filtros -->
    <BaseCard compact>
      <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-[200px]">
          <BaseInput
            v-model="search"
            placeholder="Buscar por código ou endereço..."
            @keyup.enter="applyFilters"
          >
            <template #icon>
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
            </template>
          </BaseInput>
        </div>
        <select
          v-model="statusFilter"
          @change="applyFilters"
          class="rounded-lg border border-slate-700 bg-surface-card px-4 py-2.5 text-sm text-slate-300"
        >
          <option value="">Todos os Status</option>
          <option value="online">Online</option>
          <option value="offline">Offline</option>
          <option value="alert">Em Alerta</option>
        </select>
      </div>
    </BaseCard>

    <!-- Tabela -->
    <BaseCard compact>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-700/50">
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">
                Código
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">
                Endereço
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">
                Bairro
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">Tipo</th>
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">
                Status
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-slate-500 uppercase">
                Última Leitura
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="h in store.hydrometers"
              :key="h.id"
              class="border-b border-slate-700/30 hover:bg-surface-hover transition-colors"
            >
              <td class="py-3 px-4 font-mono font-medium text-primary-400">{{ h.code }}</td>
              <td class="py-3 px-4 text-slate-300">{{ h.address }}</td>
              <td class="py-3 px-4 text-slate-400">{{ h.neighborhood }}</td>
              <td class="py-3 px-4 text-slate-400">{{ typeMap[h.type] || h.type }}</td>
              <td class="py-3 px-4"><StatusBadge :status="h.status" /></td>
              <td class="py-3 px-4 text-slate-500 text-xs">
                {{ h.last_reading_at ? new Date(h.last_reading_at).toLocaleString('pt-BR') : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div class="flex items-center justify-between border-t border-slate-700/50 pt-4 mt-4">
        <p class="text-xs text-slate-500">
          Página {{ store.pagination.currentPage }} de {{ store.pagination.lastPage }}
        </p>
        <div class="flex gap-2">
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="store.pagination.currentPage <= 1"
            @click="store.fetchHydrometers(store.pagination.currentPage - 1)"
          >
            <ChevronLeft class="h-4 w-4" />
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="store.pagination.currentPage >= store.pagination.lastPage"
            @click="store.fetchHydrometers(store.pagination.currentPage + 1)"
          >
            <ChevronRight class="h-4 w-4" />
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <!-- Modal de criação -->
    <BaseModal :open="showCreateModal" title="Novo Hidrômetro" @close="showCreateModal = false">
      <form @submit.prevent="handleCreate" class="space-y-4">
        <BaseInput v-model="form.code" label="Código" placeholder="HYD-201" :error="formErrors.code" />
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
        <BaseInput v-model="form.address" label="Endereço" placeholder="Rua das Águas, 100" :error="formErrors.address" />
        <BaseInput v-model="form.neighborhood" label="Bairro" placeholder="Centro" :error="formErrors.neighborhood" />
        <div class="space-y-1.5">
          <label class="block text-sm font-medium text-slate-300">Tipo</label>
          <select
            v-model="form.type"
            :class="['w-full rounded-lg border bg-surface-card px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2', formErrors.type ? 'border-danger focus:ring-danger/50' : 'border-slate-700 focus:ring-primary-500/50']"
          >
            <option value="residential">Residencial</option>
            <option value="commercial">Comercial</option>
            <option value="industrial">Industrial</option>
          </select>
          <p v-if="formErrors.type" class="text-xs text-danger mt-1">{{ formErrors.type }}</p>
        </div>
      </form>
      <template #footer>
        <BaseButton variant="secondary" @click="showCreateModal = false">Cancelar</BaseButton>
        <BaseButton @click="handleCreate">Criar Hidrômetro</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
