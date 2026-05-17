<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useHydrometerStore } from '@/stores/hydrometer'
import { useIsAdmin } from '@/composables/useIsAdmin'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import type { Hydrometer } from '@/types'
import { Plus, Search, ChevronLeft, ChevronRight, Pencil, Trash2 } from 'lucide-vue-next'
import { ApiError } from '@/services/api'

/**
 * View de Gerenciamento de Hidrômetros (CRUD).
 *
 * Exibe a tabela paginada com buscas e filtros integrados via HydrometerStore.
 * Controla os privilégios de acesso: apenas usuários com role 'admin'
 * têm permissão para criar, editar ou excluir hidrômetros.
 */

const store = useHydrometerStore()
const router = useRouter()
const { isAdmin } = useIsAdmin()

const search = ref('')
const statusFilter = ref('')
const showCreateModal = ref(false)

/** Estado do modal de edição */
const showEditModal = ref(false)
const editingHydrometer = ref<Hydrometer | null>(null)

/** Estado do dialog de confirmação de exclusão */
const showDeleteDialog = ref(false)
const deletingHydrometer = ref<Hydrometer | null>(null)
const deleteLoading = ref(false)

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

/** Dados do formulário de edição */
const editForm = ref({
  code: '',
  latitude: '',
  longitude: '',
  address: '',
  neighborhood: '',
  type: 'residential' as 'residential' | 'commercial' | 'industrial',
})

const formErrors = ref<Record<string, string>>({})
const editFormErrors = ref<Record<string, string>>({})

await store.fetchHydrometers()

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
    form.value = {
      code: '',
      latitude: '',
      longitude: '',
      address: '',
      neighborhood: '',
      type: 'residential',
    }
  } catch (error) {
    if (error instanceof ApiError && error.status === 422 && error.errors) {
      for (const [field, messages] of Object.entries(error.errors)) {
        formErrors.value[field] = messages[0] || 'Erro de validação'
      }
    } else {
      // eslint-disable-next-line no-console
      console.error(error)
    }
  }
}

/** Abre o modal de edição preenchido com os dados do hidrômetro */
function openEditModal(hydrometer: Hydrometer) {
  editingHydrometer.value = hydrometer
  editFormErrors.value = {}
  editForm.value = {
    code: hydrometer.code,
    latitude: String(hydrometer.latitude),
    longitude: String(hydrometer.longitude),
    address: hydrometer.address,
    neighborhood: hydrometer.neighborhood,
    type: hydrometer.type,
  }
  showEditModal.value = true
}

/** Envia as alterações do formulário de edição para a API */
async function handleEdit() {
  if (!editingHydrometer.value) return
  editFormErrors.value = {}
  try {
    await store.updateHydrometer(editingHydrometer.value.id, {
      ...editForm.value,
      latitude: editForm.value.latitude === '' ? '' : Number(editForm.value.latitude),
      longitude: editForm.value.longitude === '' ? '' : Number(editForm.value.longitude),
    } as unknown as Partial<Hydrometer>)
    showEditModal.value = false
    editingHydrometer.value = null
  } catch (error) {
    if (error instanceof ApiError && error.status === 422 && error.errors) {
      for (const [field, messages] of Object.entries(error.errors)) {
        editFormErrors.value[field] = messages[0] || 'Erro de validação'
      }
    } else {
      // eslint-disable-next-line no-console
      console.error(error)
    }
  }
}

/** Abre o dialog de confirmação de exclusão */
function openDeleteDialog(hydrometer: Hydrometer) {
  deletingHydrometer.value = hydrometer
  showDeleteDialog.value = true
}

/** Confirma a exclusão do hidrômetro selecionado */
async function confirmDelete() {
  if (!deletingHydrometer.value) return
  deleteLoading.value = true
  try {
    await store.deleteHydrometer(deletingHydrometer.value.id)
    showDeleteDialog.value = false
    deletingHydrometer.value = null
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error(error)
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <div class="animate-fade-in view-scroll-layout">
    <!-- Header -->
    <div class="flex items-center justify-between shrink-0">
      <div>
        <h1 class="text-2xl font-bold text-text-heading">Hidrômetros</h1>
        <p class="text-sm text-text-muted mt-1">
          {{ store.pagination.total }} dispositivos cadastrados
        </p>
      </div>
      <BaseButton v-if="isAdmin" @click="showCreateModal = true">
        <Plus class="h-4 w-4" /> Novo Hidrômetro
      </BaseButton>
    </div>

    <!-- Filtros -->
    <BaseCard compact class="shrink-0">
      <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-[12.5rem]">
          <BaseInput
            v-model="search"
            placeholder="Buscar por código ou endereço..."
            @keyup.enter="applyFilters"
          >
            <template #icon>
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-text-muted" />
            </template>
          </BaseInput>
        </div>
        <select
          v-model="statusFilter"
          @change="applyFilters"
          class="rounded-lg border border-border bg-surface-card px-4 py-2.5 text-sm text-text-body"
        >
          <option value="">Todos os Status</option>
          <option value="online">Online</option>
          <option value="offline">Offline</option>
          <option value="alert">Em Alerta</option>
        </select>
      </div>
    </BaseCard>

    <!-- Tabela -->
    <BaseCard compact class="view-scroll-card">
      <div class="view-scroll-content overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="sticky top-0 z-10 bg-surface-card">
            <tr class="border-b border-border">
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Código
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Endereço
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Bairro
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Tipo
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Status
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Última Leitura
              </th>
              <th
                v-if="isAdmin"
                class="text-right py-3 px-4 text-xs font-medium text-text-muted uppercase"
              >
                Ações
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="h in store.hydrometers"
              :key="h.id"
              class="border-b border-border/60 hover:bg-surface-hover transition-colors"
            >
              <td class="py-3 px-4 font-mono font-medium">
                <button
                  @click="router.push({ name: 'hydrometer-detail', params: { id: h.id } })"
                  class="text-primary-400 hover:text-primary-300 hover:underline transition-colors"
                >
                  {{ h.code }}
                </button>
              </td>
              <td class="py-3 px-4 text-text-body">{{ h.address }}</td>
              <td class="py-3 px-4 text-text-muted">{{ h.neighborhood }}</td>
              <td class="py-3 px-4 text-text-muted">{{ typeMap[h.type] || h.type }}</td>
              <td class="py-3 px-4"><StatusBadge :status="h.status" /></td>
              <td class="py-3 px-4 text-text-muted text-xs">
                {{ h.last_reading_at ? new Date(h.last_reading_at).toLocaleString('pt-BR') : '—' }}
              </td>
              <td v-if="isAdmin" class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    @click="openEditModal(h)"
                    class="rounded-lg p-1.5 text-text-muted hover:text-primary-400 hover:bg-primary-500/10 transition-colors"
                    title="Editar hidrômetro"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    @click="openDeleteDialog(h)"
                    class="rounded-lg p-1.5 text-text-muted hover:text-red-400 hover:bg-red-500/10 transition-colors"
                    title="Excluir hidrômetro"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div class="flex items-center justify-between border-t border-border pt-4 mt-4 shrink-0">
        <p class="text-xs text-text-muted">
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
        <BaseInput
          v-model="form.code"
          label="Código"
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
          label="Endereço"
          placeholder="Rua das Águas, 100"
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
        <BaseButton variant="secondary" @click="showCreateModal = false">Cancelar</BaseButton>
        <BaseButton @click="handleCreate">Criar Hidrômetro</BaseButton>
      </template>
    </BaseModal>

    <!-- Modal de edição -->
    <BaseModal :open="showEditModal" title="Editar Hidrômetro" @close="showEditModal = false">
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
          <p v-if="editFormErrors.type" class="text-xs text-danger mt-1">
            {{ editFormErrors.type }}
          </p>
        </div>
      </form>
      <template #footer>
        <BaseButton variant="secondary" @click="showEditModal = false">Cancelar</BaseButton>
        <BaseButton @click="handleEdit">Salvar Alterações</BaseButton>
      </template>
    </BaseModal>

    <!-- Dialog de confirmação de exclusão -->
    <BaseModal
      :open="showDeleteDialog"
      title="Confirmar Exclusão"
      size="sm"
      @close="showDeleteDialog = false"
    >
      <div class="text-sm text-text-body space-y-3">
        <p>
          Tem certeza que deseja excluir o hidrômetro
          <strong class="text-text-heading">{{ deletingHydrometer?.code }}</strong
          >?
        </p>
        <p class="text-text-muted">
          Esta ação é irreversível. Todas as leituras e alertas associados a este dispositivo também
          serão removidos.
        </p>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="showDeleteDialog = false">Cancelar</BaseButton>
        <BaseButton
          @click="confirmDelete"
          :loading="deleteLoading"
          class="!bg-red-600 hover:!bg-red-700 !border-red-600"
          >Excluir</BaseButton
        >
      </template>
    </BaseModal>
  </div>
</template>
