<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import Button from './Button.vue'

export type IconButtonVariant = 'default' | 'primary' | 'destructive' | 'success'

const props = withDefaults(defineProps<{
  variant?: IconButtonVariant
  title?: string
  disabled?: boolean
  type?: 'button' | 'submit' | 'reset'
  class?: string
}>(), {
  variant: 'default',
  type: 'button',
})

// Единый вид иконок-действий (правка, удаление, восстановление, публикация).
// Размер и подъём при ховере наследуются от Button, здесь только цвет по смыслу действия.
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
    :type="type"
    :title="title"
    :disabled="disabled"
    :class="cn(colorClass, props.class)"
  >
    <slot />
  </Button>
</template>
