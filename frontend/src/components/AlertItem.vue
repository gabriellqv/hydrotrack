<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import BaseButton from './ui/BaseButton.vue'
import type { Alert } from '@/types'
import { useIsAdmin } from '@/composables/useIsAdmin'
import {
  Flame,
  Droplets,
  WifiOff,
  Clock,
  CheckCircle2,
  AlertTriangle,
  ArrowRight,
  MapPin,
} from 'lucide-vue-next'

/**
 * Card de alerta individual com design de telemetria moderno.
 *
 * Exibe a tipologia da anomalia com destaque cromático, mensagem explicativa,
 * metadados do hidrômetro afetado (código, bairro), timestamp e ação de resolução.
 *
 * @prop {Alert} alert - Dados completos do alerta
 * @emits resolve - Emitido quando o admin resolve o alerta
 */
const props = defineProps<{
  alert: Alert
}>()

const emit = defineEmits<{
  resolve: [id: number]
}>()

const { isAdmin } = useIsAdmin()

const typeConfig = computed(() => {
  switch (props.alert.type) {
    case 'high_consumption':
      return {
        label: 'Consumo Excessivo',
        icon: Flame,
        colorClass: 'text-red-400 bg-red-500/10 border-red-500/25',
        accentBorder: 'border-l-red-500',
        dotColor: 'bg-red-500',
      }
    case 'zero_reading':
      return {
        label: 'Leitura Zero',
        icon: Droplets,
        colorClass: 'text-amber-400 bg-amber-500/10 border-amber-500/25',
        accentBorder: 'border-l-amber-500',
        dotColor: 'bg-amber-500',
      }
    case 'offline':
      return {
        label: 'Sem Comunicação',
        icon: WifiOff,
        colorClass: 'text-slate-400 bg-slate-500/10 border-slate-500/25',
        accentBorder: 'border-l-slate-500',
        dotColor: 'bg-slate-500',
      }
    default:
      return {
        label: props.alert.type,
        icon: AlertTriangle,
        colorClass: 'text-primary-400 bg-primary-500/10 border-primary-500/25',
        accentBorder: 'border-l-primary-500',
        dotColor: 'bg-primary-500',
      }
  }
})
</script>

<template>
  <div
    :class="[
      'group relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 rounded-2xl border border-l-4 transition-all duration-200 shadow-sm',
      typeConfig.accentBorder,
      alert.resolved
        ? 'bg-surface/50 border-border/40 opacity-70 hover:opacity-100'
        : 'bg-surface-card border-border hover:border-border-hover hover:shadow-md',
    ]"
  >
    <!-- Lado Esquerdo: Ícone + Conteúdo -->
    <div class="flex items-start gap-3.5 flex-1 min-w-0">
      <!-- Ícone temático da categoria -->
      <div
        :class="[
          'w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 mt-0.5 sm:mt-0',
          typeConfig.colorClass,
        ]"
      >
        <component :is="typeConfig.icon" class="h-5 w-5" />
      </div>

      <!-- Detalhes do Alerta -->
      <div class="flex-1 min-w-0">
        <!-- Badges Superiores -->
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span
            :class="[
              'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
              typeConfig.colorClass,
            ]"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="typeConfig.dotColor"></span>
            {{ typeConfig.label }}
          </span>

          <!-- Status do Alerta -->
          <span
            v-if="alert.resolved"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
          >
            <CheckCircle2 class="h-3 w-3" />
            Resolvido
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
            Pendente
          </span>
        </div>

        <!-- Mensagem do Alerta -->
        <p class="text-sm font-medium text-text-heading leading-snug break-words">
          {{ alert.message }}
        </p>

        <!-- Metadados: Hidrômetro, Localização e Timestamp -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-2 text-xs text-text-muted">
          <!-- Link do Hidrômetro -->
          <RouterLink
            v-if="alert.hydrometer && (alert.hydrometer.id || alert.hydrometer_id)"
            :to="{
              name: 'hydrometer-detail',
              params: { id: alert.hydrometer.id || alert.hydrometer_id },
            }"
            class="inline-flex items-center gap-1 font-mono font-bold text-primary-400 hover:text-primary-300 hover:underline transition-colors"
          >
            {{ alert.hydrometer.code }}
            <ArrowRight class="h-3 w-3" />
          </RouterLink>

          <!-- Bairro / Endereço (se disponível) -->
          <span
            v-if="alert.hydrometer?.neighborhood"
            class="inline-flex items-center gap-1 text-text-muted"
          >
            <MapPin class="h-3 w-3" />
            {{ alert.hydrometer.neighborhood }}
          </span>

          <!-- Timestamp -->
          <span class="inline-flex items-center gap-1 text-text-muted">
            <Clock class="h-3 w-3" />
            {{ new Date(alert.created_at).toLocaleString('pt-BR') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Lado Direito: Ação de Resolução -->
    <div
      class="w-full sm:w-auto shrink-0 flex items-center justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-border/40"
    >
      <BaseButton
        v-if="isAdmin && !alert.resolved"
        variant="secondary"
        size="sm"
        @click="emit('resolve', alert.id)"
        class="w-full sm:w-auto justify-center text-xs font-semibold hover:!border-emerald-500/50 hover:!text-emerald-400 transition-colors"
      >
        <CheckCircle2 class="h-3.5 w-3.5 mr-1.5 text-emerald-400" />
        Resolver Alerta
      </BaseButton>

      <span
        v-else-if="alert.resolved"
        class="text-xs text-text-muted font-medium italic sm:text-right"
      >
        {{
          alert.resolved_at
            ? `Tratado em ${new Date(alert.resolved_at).toLocaleDateString('pt-BR')}`
            : 'Tratado'
        }}
      </span>
    </div>
  </div>
</template>
