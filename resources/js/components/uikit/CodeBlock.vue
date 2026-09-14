<script setup lang="ts">
import { cn } from '@/lib/utils'

const props = defineProps<{
  code: string
  class?: string
}>()
</script>

<template>
  <pre
    :class="cn(
      'overflow-x-auto rounded-xl border border-border bg-muted/40 p-4 text-xs leading-relaxed text-foreground/90',
      props.class,
    )"
  ><code>{{ props.code }}</code></pre>
</template>
