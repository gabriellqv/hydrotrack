<template>
  <div
    :class="[
      'animate-pulse bg-surface-hover rounded-md',
      {
        'rounded-full': circle,
      },
    ]"
  ></div>
</template>

<script setup lang="ts">
defineProps({
  circle: {
    type: Boolean,
    default: false,
  },
})
</script>
