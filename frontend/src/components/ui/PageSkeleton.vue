/** * Skeleton genérico de página com header e grid de conteúdo. */

<template>
  <div class="flex flex-col gap-6 animate-pulse p-2">
    <div class="flex flex-col gap-2">
      <Skeleton class="h-8 w-1/3 max-w-[300px]" />
      <Skeleton class="h-4 w-1/2 max-w-[400px]" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Skeleton class="h-[300px] w-full rounded-xl" />
      <Skeleton class="h-[300px] w-full rounded-xl" />
      <Skeleton class="h-[250px] w-full rounded-xl" />
      <Skeleton class="h-[250px] w-full rounded-xl" />
    </div>
  </div>
</template>

<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>
