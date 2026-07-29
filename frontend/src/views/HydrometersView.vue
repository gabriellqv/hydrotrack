<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useHydrometerStore } from '@/stores/hydrometer'
import { useIsAdmin } from '@/composables/useIsAdmin'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import CreateHydrometerModal from '@/components/CreateHydrometerModal.vue'
import EditHydrometerModal from '@/components/EditHydrometerModal.vue'
import DeleteHydrometerDialog from '@/components/DeleteHydrometerDialog.vue'
import { HYDROMETER_TYPE_LABELS } from '@/constants'
import type { Hydrometer } from '@/types'
import { Plus, Search, ChevronLeft, ChevronRight, Pencil, Trash2 } from 'lucide-vue-next'

/**
 * View de Gerenciamento de Hidrometros (CRUD).
 *
 * Exibe a tabela paginada com buscas e filtros integrados via HydrometerStore.
 * Apenas administradores podem criar, editar ou excluir hidrometros.
 */

const store = useHydrometerStore()
const router = useRouter()
const { isAdmin } = useIsAdmin()

const search = ref('')
const statusFilter = ref('')

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingHydrometer = ref<Hydrometer | null>(null)
const showDeleteDialog = ref(false)
const deletingHydrometer = ref<Hydrometer | null>(null)

await store.fetchHydrometers()

function applyFilters() {
  const filters: Record<string, string> = {}
  if (statusFilter.value) filters.status = statusFilter.value
  if (search.value) filters.search = search.value
  store.fetchHydrometers(1, filters)
}

function openEditModal(hydrometer: Hydrometer) {
  editingHydrometer.value = hydrometer
  showEditModal.value = true
}

function openDeleteDialog(hydrometer: Hydrometer) {
  deletingHydrometer.value = hydrometer
  showDeleteDialog.value = true
}
</script>

<template>
  <div class="animate-fade-in view-scroll-layout">
    <!-- Header -->
    <div class="flex items-center justify-between shrink-0">
      <div>
        <h1 class="text-2xl font-bold text-text-heading">Hidrometros</h1>
        <p class="text-sm text-text-muted mt-1">
          {{ store.pagination.total }} dispositivos cadastrados
        </p>
      </div>
      <BaseButton v-if="isAdmin" @click="showCreateModal = true">
        <Plus class="h-4 w-4" /> Novo Hidrometro
      </BaseButton>
    </div>

    <!-- Filtros -->
    <BaseCard compact class="shrink-0">
      <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-[12.5rem]">
          <BaseInput
            v-model="search"
            placeholder="Buscar por codigo ou endereco..."
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
                Codigo
              </th>
              <th class="text-left py-3 px-4 text-xs font-medium text-text-muted uppercase">
                Endereco
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
                Ultima Leitura
              </th>
              <th
                v-if="isAdmin"
                class="text-right py-3 px-4 text-xs font-medium text-text-muted uppercase"
              >
                Acoes
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
              <td class="py-3 px-4 text-text-muted">{{ HYDROMETER_TYPE_LABELS[h.type] }}</td>
              <td class="py-3 px-4"><StatusBadge :status="h.status" /></td>
              <td class="py-3 px-4 text-text-muted text-xs">
                {{ h.last_reading_at ? new Date(h.last_reading_at).toLocaleString('pt-BR') : '—' }}
              </td>
              <td v-if="isAdmin" class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    @click="openEditModal(h)"
                    class="rounded-lg p-1.5 text-text-muted hover:text-primary-400 hover:bg-primary-500/10 transition-colors"
                    title="Editar hidrometro"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    @click="openDeleteDialog(h)"
                    class="rounded-lg p-1.5 text-text-muted hover:text-red-400 hover:bg-red-500/10 transition-colors"
                    title="Excluir hidrometro"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginacao -->
      <div class="flex items-center justify-between border-t border-border pt-4 mt-4 shrink-0">
        <p class="text-xs text-text-muted">
          Pagina {{ store.pagination.currentPage }} de {{ store.pagination.lastPage }}
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

    <!-- Modais -->
    <CreateHydrometerModal v-if="showCreateModal" @close="showCreateModal = false" />
    <EditHydrometerModal
      v-if="showEditModal && editingHydrometer"
      :hydrometer="editingHydrometer"
      @close="showEditModal = false; editingHydrometer = null"
    />
    <DeleteHydrometerDialog
      v-if="showDeleteDialog && deletingHydrometer"
      :hydrometer="deletingHydrometer"
      @close="showDeleteDialog = false; deletingHydrometer = null"
    />
  </div>
</template>
