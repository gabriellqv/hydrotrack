<script setup lang="ts">
import { ref, onMounted, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { useHydrometerStore } from '@/stores/hydrometer'
import { useToastStore } from '@/stores/toast'
import { useIsAdmin } from '@/composables/useIsAdmin'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import type { Hydrometer } from '@/types'
import {
  Plus,
  Search,
  ChevronLeft,
  ChevronRight,
  Pencil,
  Trash2,
  Home,
  Building2,
  Factory,
  Eye,
  X,
  Radio,
  RefreshCw,
} from 'lucide-vue-next'
import { ApiError } from '@/services/api'

/**
 * View de Gerenciamento de Hidrômetros (CRUD).
 *
 * Exibe a tabela paginada com buscas e filtros integrados via HydrometerStore.
 * Controla os privilégios de acesso: apenas usuários com role 'admin'
 * têm permissão para criar, editar ou excluir hidrômetros.
 */
const store = useHydrometerStore()
const toast = useToastStore()
const router = useRouter()
const { isAdmin } = useIsAdmin()

const search = ref('')
const statusFilter = ref<'' | 'online' | 'offline' | 'alert'>('')
const showCreateModal = ref(false)

/** Estado do modal de edição */
const showEditModal = ref(false)
const editingHydrometer = ref<Hydrometer | null>(null)

/** Estado do dialog de confirmação de exclusão */
const showDeleteDialog = ref(false)
const deletingHydrometer = ref<Hydrometer | null>(null)
const deleteLoading = ref(false)

const typeMap: Record<string, { label: string; icon: typeof Home }> = {
  residential: { label: 'Residencial', icon: Home },
  commercial: { label: 'Comercial', icon: Building2 },
  industrial: { label: 'Industrial', icon: Factory },
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

onMounted(() => {
  store.fetchHydrometers()
})

onActivated(() => {
  store.fetchHydrometers(store.pagination.currentPage)
})

function setStatusFilter(status: '' | 'online' | 'offline' | 'alert') {
  statusFilter.value = status
  applyFilters()
}

function clearSearch() {
  search.value = ''
  applyFilters()
}

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
      toast.error(error instanceof Error ? error.message : 'Erro ao cadastrar hidrômetro')
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
      toast.error(error instanceof Error ? error.message : 'Erro ao atualizar hidrômetro')
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
    toast.error(error instanceof Error ? error.message : 'Erro ao excluir hidrômetro')
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <div class="animate-fade-in space-y-6 pb-12">
    <!-- Header da Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl lg:text-3xl font-bold text-text-heading tracking-tight">Hidrômetros</h1>
        <p class="text-sm text-text-muted mt-0.5">
          Gestão e telemetria de {{ store.pagination.total }} dispositivos cadastrados na malha
        </p>
      </div>

      <!-- Botão Novo Hidrômetro (Padrão Pill) -->
      <BaseButton v-if="isAdmin" variant="primary" @click="showCreateModal = true">
        <Plus class="h-4 w-4 stroke-[2.5]" />
        <span>Novo Hidrômetro</span>
      </BaseButton>
    </div>

    <!-- Barra de Filtros e Busca (Estilo Pill) -->
    <BaseCard compact class="p-4 rounded-2xl shadow-sm">
      <div class="flex flex-col lg:flex-row lg:items-center gap-4 justify-between">
        <!-- Campo de Busca -->
        <div class="relative flex-1 min-w-[16rem]">
          <BaseInput
            v-model="search"
            placeholder="Buscar por código, endereço ou bairro..."
            @keyup.enter="applyFilters"
          >
            <template #icon>
              <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-text-muted" />
            </template>
          </BaseInput>
          <button
            v-if="search"
            @click="clearSearch"
            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-text-muted hover:text-text-heading transition-colors"
            title="Limpar busca"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>

        <!-- Filtros de Status em Pills -->
        <div
          class="flex flex-wrap items-center gap-1.5 shrink-0 bg-surface/50 p-1.5 rounded-full border border-border/60"
        >
          <button
            @click="setStatusFilter('')"
            :class="[
              'px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
              statusFilter === ''
                ? 'bg-primary-600 text-white shadow-sm'
                : 'text-text-muted hover:text-text-heading hover:bg-surface-hover/50',
            ]"
          >
            Todos
          </button>

          <button
            @click="setStatusFilter('online')"
            :class="[
              'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
              statusFilter === 'online'
                ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-sm'
                : 'text-text-muted hover:text-emerald-400 hover:bg-surface-hover/50',
            ]"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Online</span>
          </button>

          <button
            @click="setStatusFilter('offline')"
            :class="[
              'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
              statusFilter === 'offline'
                ? 'bg-slate-500/20 text-slate-300 border border-slate-500/40 shadow-sm'
                : 'text-text-muted hover:text-slate-300 hover:bg-surface-hover/50',
            ]"
          >
            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
            <span>Offline</span>
          </button>

          <button
            @click="setStatusFilter('alert')"
            :class="[
              'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
              statusFilter === 'alert'
                ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40 shadow-sm'
                : 'text-text-muted hover:text-rose-400 hover:bg-surface-hover/50',
            ]"
          >
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>Em Alerta</span>
          </button>
        </div>
      </div>
    </BaseCard>

    <!-- Tabela Corporativa de Hidrômetros -->
    <BaseCard compact class="p-0 rounded-2xl overflow-hidden shadow-sm border-border/60">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface/70 border-b border-border/60 backdrop-blur-sm">
            <tr>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Código
              </th>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Endereço
              </th>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Bairro
              </th>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Tipo
              </th>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="text-left py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Última Leitura
              </th>
              <th
                class="text-right py-3.5 px-5 text-xs font-bold text-text-muted uppercase tracking-wider"
              >
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/40">
            <tr
              v-for="h in store.hydrometers"
              :key="h.id"
              class="hover:bg-surface-hover/40 transition-colors group"
            >
              <!-- Código com Badge Clicável -->
              <td class="py-3.5 px-5 font-mono">
                <button
                  @click="router.push({ name: 'hydrometer-detail', params: { id: h.id } })"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary-500/10 text-primary-400 hover:bg-primary-500/20 font-bold text-xs transition-all cursor-pointer group-hover:scale-105"
                  title="Ver histórico de telemetria"
                >
                  <Radio class="h-3 w-3" />
                  <span>{{ h.code }}</span>
                </button>
              </td>

              <!-- Endereço -->
              <td class="py-3.5 px-5 text-text-body font-medium">
                {{ h.address }}
              </td>

              <!-- Bairro -->
              <td class="py-3.5 px-5 text-text-muted">
                {{ h.neighborhood }}
              </td>

              <!-- Tipo do Imóvel com Ícone -->
              <td class="py-3.5 px-5 text-text-muted">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                  <component
                    :is="typeMap[h.type]?.icon || Home"
                    class="h-3.5 w-3.5 text-text-muted/80"
                  />
                  <span>{{ typeMap[h.type]?.label || h.type }}</span>
                </span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-5">
                <StatusBadge :status="h.status" />
              </td>

              <!-- Última Leitura -->
              <td class="py-3.5 px-5 text-text-muted text-xs tabular-nums">
                {{
                  h.last_reading_at
                    ? new Date(h.last_reading_at).toLocaleString('pt-BR')
                    : 'Sem leitura'
                }}
              </td>

              <!-- Ações -->
              <td class="py-3.5 px-5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="router.push({ name: 'hydrometer-detail', params: { id: h.id } })"
                    class="p-1.5 rounded-lg text-text-muted hover:text-primary-400 hover:bg-primary-500/10 transition-colors cursor-pointer"
                    title="Detalhes do hidrômetro"
                  >
                    <Eye class="h-4 w-4" />
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="openEditModal(h)"
                    class="p-1.5 rounded-lg text-text-muted hover:text-amber-400 hover:bg-amber-500/10 transition-colors cursor-pointer"
                    title="Editar hidrômetro"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="openDeleteDialog(h)"
                    class="p-1.5 rounded-lg text-text-muted hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                    title="Excluir hidrômetro"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- Loading Inicial -->
            <tr v-if="!store.hydrometers.length && store.loading">
              <td colspan="7" class="py-12 text-center text-text-muted">
                <RefreshCw class="h-6 w-6 mx-auto text-primary-400 animate-spin mb-2" />
                <p class="text-sm font-semibold">Carregando hidrômetros...</p>
              </td>
            </tr>

            <!-- Estado Vazio -->
            <tr v-if="!store.hydrometers.length && !store.loading">
              <td colspan="7" class="py-12 text-center text-text-muted">
                <Radio class="h-8 w-8 mx-auto text-text-muted/40 mb-2" />
                <p class="text-sm font-semibold">Nenhum hidrômetro encontrado</p>
                <p class="text-xs text-text-muted/70 mt-0.5">
                  Tente ajustar seus filtros ou termo de busca.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação Moderna -->
      <div
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-4 border-t border-border/50 bg-surface/30"
      >
        <p class="text-xs text-text-muted">
          Exibindo página
          <strong class="text-text-heading">{{ store.pagination.currentPage }}</strong> de
          <strong class="text-text-heading">{{ store.pagination.lastPage }}</strong> ({{
            store.pagination.total
          }}
          no total)
        </p>
        <div class="flex items-center gap-2">
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="store.pagination.currentPage <= 1"
            @click="store.fetchHydrometers(store.pagination.currentPage - 1)"
          >
            <ChevronLeft class="h-3.5 w-3.5" />
            <span>Anterior</span>
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="store.pagination.currentPage >= store.pagination.lastPage"
            @click="store.fetchHydrometers(store.pagination.currentPage + 1)"
          >
            <span>Próxima</span>
            <ChevronRight class="h-3.5 w-3.5" />
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <!-- Modal de Criação -->
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
          <label class="block text-sm font-medium text-text-body">Tipo de Imóvel</label>
          <select
            v-model="form.type"
            :class="[
              'w-full rounded-xl border bg-surface-card px-4 py-2.5 text-sm text-text-heading focus:outline-none focus:ring-2',
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
        <BaseButton variant="primary" @click="handleCreate">Criar Hidrômetro</BaseButton>
      </template>
    </BaseModal>

    <!-- Modal de Edição -->
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
          <label class="block text-sm font-medium text-text-body">Tipo de Imóvel</label>
          <select
            v-model="editForm.type"
            :class="[
              'w-full rounded-xl border bg-surface-card px-4 py-2.5 text-sm text-text-heading focus:outline-none focus:ring-2',
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
        <BaseButton variant="primary" @click="handleEdit">Salvar Alterações</BaseButton>
      </template>
    </BaseModal>

    <!-- Dialog de Confirmação de Exclusão -->
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
        <BaseButton variant="danger" @click="confirmDelete" :loading="deleteLoading">
          Excluir
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
