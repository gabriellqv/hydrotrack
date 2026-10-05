<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import type { Hydrometer } from '@/types'
import StatusBadge from '@/components/StatusBadge.vue'
import {
  MapPin,
  Building2,
  Home,
  Factory,
  Navigation,
  Copy,
  Check,
  ExternalLink,
  X,
  Crosshair,
} from 'lucide-vue-next'

defineProps<{
  hydrometer: Hydrometer
  isDark: boolean
}>()

const emit = defineEmits<{
  close: []
  center: []
}>()

const copiedCoord = ref(false)
const copiedCode = ref(false)

const typeMap: Record<string, { label: string; icon: typeof Home }> = {
  residential: { label: 'Residencial', icon: Home },
  commercial: { label: 'Comercial', icon: Building2 },
  industrial: { label: 'Industrial', icon: Factory },
}

function copyToClipboard(text: string, type: 'coord' | 'code') {
  navigator.clipboard.writeText(text)
  if (type === 'coord') {
    copiedCoord.value = true
    setTimeout(() => {
      copiedCoord.value = false
    }, 2000)
  } else {
    copiedCode.value = true
    setTimeout(() => {
      copiedCode.value = false
    }, 2000)
  }
}
</script>

<template>
  <div
    class="h-full flex flex-col justify-between overflow-y-auto custom-scrollbar rounded-2xl p-5 border shadow-2xl transition-all duration-300"
    :class="[
      isDark
        ? 'bg-[#101724] border-slate-700/60 shadow-black/80 ring-1 ring-white/10'
        : 'bg-white border-slate-200/90 shadow-slate-900/10 ring-1 ring-black/5',
    ]"
  >
    <div class="space-y-4">
      <!-- Topo do Card com Código e Fechar -->
      <div class="flex items-center justify-between pb-3 border-b border-border/50">
        <div class="flex items-center gap-2">
          <span
            class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg"
            :class="[
              isDark
                ? 'text-sky-400 bg-sky-500/15 border border-sky-500/30 shadow-[0_0_12px_rgba(56,189,248,0.2)]'
                : 'text-primary-700 bg-primary-50 border border-primary-200',
            ]"
          >
            {{ hydrometer.code }}
          </span>
          <button
            type="button"
            @click="copyToClipboard(hydrometer.code, 'code')"
            class="p-1.5 rounded-lg text-text-muted hover:text-text-heading hover:bg-surface-hover transition-colors cursor-pointer"
            :title="copiedCode ? 'Copiado!' : 'Copiar código'"
          >
            <Check v-if="copiedCode" class="h-3.5 w-3.5 text-emerald-400" />
            <Copy v-else class="h-3.5 w-3.5" />
          </button>
        </div>

        <button
          type="button"
          @click="emit('close')"
          class="p-1.5 rounded-lg text-text-muted hover:text-text-heading hover:bg-surface-hover transition-colors cursor-pointer"
          title="Fechar inspeção"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Status e Tipologia -->
      <div class="flex items-center justify-between gap-2">
        <StatusBadge :status="hydrometer.status" />

        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
          :class="[
            isDark
              ? 'bg-[#162232] text-slate-300 border border-white/10'
              : 'bg-slate-100 text-slate-700 border border-slate-200',
          ]"
        >
          <component
            :is="typeMap[hydrometer.type]?.icon || Home"
            class="h-3.5 w-3.5 text-text-muted"
          />
          {{ typeMap[hydrometer.type]?.label || hydrometer.type }}
        </span>
      </div>

      <!-- Informações Geográficas -->
      <div
        class="space-y-2.5 rounded-xl p-3.5 border transition-colors"
        :class="[
          isDark
            ? 'bg-[#15202e] border-white/10 shadow-inner'
            : 'bg-slate-50 border-slate-200/80 shadow-sm',
        ]"
      >
        <div>
          <span
            class="text-[10px] font-bold text-text-muted uppercase tracking-wider block mb-0.5"
          >
            Endereço
          </span>
          <div class="flex items-start gap-1.5 text-xs text-text-heading font-medium">
            <MapPin class="h-3.5 w-3.5 text-primary-400 shrink-0 mt-0.5" />
            <span>{{ hydrometer.address }}</span>
          </div>
        </div>

        <div class="pt-2 border-t border-border/30">
          <span
            class="text-[10px] font-bold text-text-muted uppercase tracking-wider block mb-0.5"
          >
            Bairro
          </span>
          <div class="flex items-center gap-1.5 text-xs text-text-body font-medium">
            <Navigation class="h-3.5 w-3.5 text-text-muted shrink-0" />
            <span>{{ hydrometer.neighborhood }}</span>
          </div>
        </div>

        <div class="pt-2 border-t border-border/30">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">
              Coordenadas GPS
            </span>
            <button
              type="button"
              @click="copyToClipboard(`${hydrometer.latitude}, ${hydrometer.longitude}`, 'coord')"
              class="text-[11px] text-primary-400 hover:text-primary-300 flex items-center gap-1 transition-colors cursor-pointer"
            >
              <Check v-if="copiedCoord" class="h-3 w-3 text-emerald-400" />
              <Copy v-else class="h-3 w-3" />
              {{ copiedCoord ? 'Copiado!' : 'Copiar' }}
            </button>
          </div>
          <div class="text-[11px] font-mono text-primary-400/90 font-medium mt-0.5">
            {{ hydrometer.latitude }}, {{ hydrometer.longitude }}
          </div>
        </div>
      </div>

      <!-- Status do Dispositivo IoT -->
      <div
        class="rounded-xl p-3.5 border space-y-2 transition-colors"
        :class="[
          isDark
            ? 'bg-[#15202e] border-white/10 shadow-inner'
            : 'bg-slate-50 border-slate-200/80 shadow-sm',
        ]"
      >
        <span
          class="text-[10px] font-bold text-text-muted uppercase tracking-wider block"
        >
          Comunicação IoT
        </span>
        <div class="flex items-center justify-between text-xs">
          <span class="text-text-muted">Protocolo</span>
          <span
            class="text-text-heading font-mono text-[11px] font-semibold bg-primary-500/10 px-2 py-0.5 rounded-md border border-primary-500/20"
          >
            MQTT / CoAP
          </span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-text-muted">Telemetria</span>
          <span class="text-emerald-400 font-bold flex items-center gap-1.5">
            <span
              class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"
            ></span>
            Sincronizado
          </span>
        </div>
      </div>
    </div>

    <!-- Ações do Hidrômetro Selecionado -->
    <div class="pt-4 border-t border-border/50 space-y-2 mt-4">
      <RouterLink
        :to="{
          name: 'hydrometer-detail',
          params: { id: hydrometer.id },
        }"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-primary-600 via-primary-500 to-sky-500 hover:from-primary-500 hover:to-sky-400 active:scale-[0.98] transition-all shadow-md shadow-primary-500/25 cursor-pointer select-none"
      >
        <span>Ver Detalhes &amp; Histórico</span>
        <ExternalLink class="h-3.5 w-3.5 ml-1.5 shrink-0" />
      </RouterLink>

      <button
        type="button"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold active:scale-[0.98] transition-all shadow-sm cursor-pointer select-none"
        :class="[
          isDark
            ? 'text-slate-200 hover:text-white bg-[#172232] hover:bg-[#1e2e42] border border-white/10'
            : 'text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-200',
        ]"
        @click="emit('center')"
      >
        <Crosshair class="h-3.5 w-3.5 mr-1.5 text-primary-400" />
        Centralizar no Ponto
      </button>
    </div>
  </div>
</template>
