<script setup lang="ts">
import { cn } from '@/lib/utils'

interface Props {
  class?: string
}

const props = defineProps<Props>()
</script>

<template>
  <h3 :class="cn('text-2xl font-semibold leading-none tracking-tight text-foreground', props.class)">
    <slot />
  </h3>
</template>