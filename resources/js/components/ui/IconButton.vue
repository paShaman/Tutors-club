<script setup lang="ts">
import { computed, type Component } from 'vue'
import { cn } from '@/lib/utils'
import Button from './Button.vue'

export type IconButtonVariant = 'default' | 'primary' | 'destructive' | 'success'

const props = withDefaults(defineProps<{
  variant?: IconButtonVariant
  title?: string
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  as?: string | Component
  lift?: boolean
  class?: string
}>(), {
  variant: 'default',
  type: 'button',
  as: 'button',
  lift: true,
})

// Единый вид иконок-действий (правка, удаление, восстановление, публикация).
// Размер наследуется от Button, здесь — цвет по смыслу действия и подъём при ховере.
const colorClass = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'text-primary hover:bg-primary/10 hover:text-primary'
    case 'destructive':
      return 'text-destructive hover:bg-destructive/10 hover:text-destructive'
    case 'success':
      return 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700'
    default:
      return 'text-muted-foreground hover:bg-accent hover:text-foreground'
  }
})
</script>

<template>
  <Button
    variant="ghost"
    size="icon-sm"
    :as="as"
    :type="as === 'button' ? type : undefined"
    :title="title"
    :disabled="disabled"
    :class="cn(colorClass, lift && 'hover:-translate-y-0.5', props.class)"
  >
    <slot />
  </Button>
</template>
