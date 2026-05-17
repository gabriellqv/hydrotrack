<template>
  <div class="flex flex-col min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] space-y-3 lg:min-h-0 animate-pulse p-4 lg:p-5">
    <div class="shrink-0 mb-4">
      <Skeleton class="h-8 w-48 mb-2" />
      <Skeleton class="h-4 w-64" />
    </div>

    <!-- Unified Dashboard Wrapper Skeleton -->
    <div class="flex-1 flex flex-col lg:min-h-0">
      <div class="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-3 gap-6 lg:gap-8 flex-1">
        
        <!-- Top Left: Gráfico Normal -->
        <div class="flex flex-col lg:row-span-2 bg-surface-card border border-border/50 rounded-xl p-4 lg:p-5">
          <Skeleton class="h-5 w-40 mb-3" />
          <div class="flex-1 w-full bg-surface-hover/50 rounded-lg border border-border/30 p-4 flex items-end gap-2">
            <!-- Simulated chart bars -->
            <Skeleton v-for="i in 12" :key="i" class="flex-1 rounded-t-sm" :style="{ height: `${Math.max(20, Math.random() * 100)}%` }" />
          </div>
        </div>

        <!-- Top Right: Mapa -->
        <div class="flex flex-col lg:row-span-2 bg-surface-card border border-border/50 rounded-xl p-4 lg:p-5">
          <Skeleton class="h-5 w-48 mb-3" />
          <!-- Data ribbon skeleton -->
          <div class="flex gap-2 mb-3 h-12">
             <Skeleton class="flex-1 rounded-lg" />
             <Skeleton class="flex-1 rounded-lg" />
             <Skeleton class="flex-1 rounded-lg" />
             <Skeleton class="flex-1 rounded-lg" />
          </div>
          <Skeleton class="flex-1 w-full rounded-lg" />
        </div>

        <!-- Bottom Left: Gráfico Pizza -->
        <div class="flex flex-col lg:row-span-1 bg-surface-card border border-border/50 rounded-xl p-4 lg:p-5">
          <Skeleton class="h-5 w-40 mb-2" />
          <div class="flex-1 flex items-center justify-center">
            <Skeleton circle class="h-32 w-32 md:h-40 md:w-40" />
          </div>
        </div>

        <!-- Bottom Right: Últimos Alertas -->
        <div class="flex flex-col lg:row-span-1 bg-surface-card border border-border/50 rounded-xl p-4 lg:p-5">
          <Skeleton class="h-5 w-32 mb-2" />
          <div class="flex-1 space-y-2">
            <Skeleton class="h-12 w-full rounded-lg" />
            <Skeleton class="h-12 w-full rounded-lg" />
            <Skeleton class="h-12 w-full rounded-lg" />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>
