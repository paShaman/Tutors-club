<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: string
}>()
</script>

<template>
  <td :class="cn('px-4 py-3 align-middle text-foreground', props.class)">
    <slot />
  </td>
</template>
