<template>
  <div class="space-y-6 lg:space-y-8 pb-10 animate-pulse">
    <!-- Header Skeleton -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <Skeleton class="h-8 w-48 mb-2" />
        <Skeleton class="h-4 w-72" />
      </div>
      <Skeleton class="h-8 w-44 rounded-full" />
    </div>

    <!-- 6 KPI Cards Skeleton -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 lg:gap-4">
      <div
        v-for="i in 6"
        :key="i"
        class="bg-surface-card border border-border/50 rounded-xl p-4 flex flex-col justify-between"
      >
        <div class="flex justify-between items-center mb-2">
          <Skeleton class="h-3 w-16" />
          <Skeleton class="h-6 w-6 rounded-lg" />
        </div>
        <Skeleton class="h-7 w-20 my-1" />
        <div class="mt-2 pt-2 border-t border-border/30">
          <Skeleton class="h-2.5 w-24" />
        </div>
      </div>
    </div>

    <!-- Main Grid Skeleton: Chart + Map -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">
      <div class="xl:col-span-7 bg-surface-card border border-border/50 rounded-xl p-5 lg:p-6">
        <div class="flex justify-between items-center mb-4">
          <div class="flex items-center gap-3">
            <Skeleton class="h-9 w-9 rounded-xl" />
            <div>
              <Skeleton class="h-4 w-36 mb-1.5" />
              <Skeleton class="h-3 w-48" />
            </div>
          </div>
          <Skeleton class="h-7 w-28 rounded-xl" />
        </div>
        <Skeleton class="h-[320px] sm:h-[350px] lg:h-[380px] w-full rounded-xl" />
      </div>

      <div class="xl:col-span-5 bg-surface-card border border-border/50 rounded-xl p-5 lg:p-6">
        <div class="flex justify-between items-center mb-4">
          <div class="flex items-center gap-3">
            <Skeleton class="h-9 w-9 rounded-xl" />
            <div>
              <Skeleton class="h-4 w-40 mb-1.5" />
              <Skeleton class="h-3 w-48" />
            </div>
          </div>
          <Skeleton class="h-6 w-20 rounded-lg" />
        </div>
        <Skeleton class="h-[320px] sm:h-[350px] lg:h-[380px] w-full rounded-xl" />
      </div>
    </div>

    <!-- Bottom Grid Skeleton: Donut + Alerts -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 lg:gap-8">
      <div class="xl:col-span-5 bg-surface-card border border-border/50 rounded-xl p-5 lg:p-6">
        <div class="flex items-center gap-3 mb-4">
          <Skeleton class="h-9 w-9 rounded-xl" />
          <div>
            <Skeleton class="h-4 w-36 mb-1.5" />
            <Skeleton class="h-3 w-44" />
          </div>
        </div>
        <div class="h-[270px] lg:h-[290px] flex items-center justify-center">
          <Skeleton circle class="h-36 w-36 lg:h-44 lg:w-44" />
        </div>
      </div>

      <div class="xl:col-span-7 bg-surface-card border border-border/50 rounded-xl p-5 lg:p-6">
        <div class="flex justify-between items-center mb-4">
          <div class="flex items-center gap-3">
            <Skeleton class="h-9 w-9 rounded-xl" />
            <div>
              <Skeleton class="h-4 w-36 mb-1.5" />
              <Skeleton class="h-3 w-52" />
            </div>
          </div>
          <Skeleton class="h-6 w-24 rounded-lg" />
        </div>
        <div class="h-[270px] lg:h-[290px] space-y-2.5">
          <Skeleton v-for="i in 4" :key="i" class="h-12 w-full rounded-lg" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>
