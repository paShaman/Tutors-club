<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import { useI18n } from '@/lib/i18n'

const props = defineProps<{
  status: string
  class?: string
}>()

const { t } = useI18n()

const label = computed(() => t(`ui.planning.status.${props.status}`))

const classes = computed(() => {
  switch (props.status) {
    case 'mastered':
      return 'bg-emerald-100 text-emerald-700'
    case 'in_progress':
      return 'bg-amber-100 text-amber-700'
    default:
      return 'bg-foreground/5 text-muted-foreground'
  }
})
</script>

<template>
  <span
    :class="cn(
      'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap',
      classes,
      props.class,
    )"
  >
    {{ label }}
  </span>
</template>
