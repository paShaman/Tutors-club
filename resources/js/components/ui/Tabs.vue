<script setup lang="ts">
import type { Component } from 'vue'
import { cn } from '@/lib/utils'

export type TabItem = {
  value: string
  label: string
  icon?: Component
}

const props = withDefaults(defineProps<{
  modelValue: string
  items: TabItem[]
  class?: string
}>(), {
  class: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div :class="cn('inline-flex flex-wrap items-center rounded-xl border border-border bg-white/60 p-1', props.class)">
    <button
      v-for="item in props.items"
      :key="item.value"
      type="button"
      :class="cn(
        'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
        item.value === props.modelValue
          ? 'bg-primary text-primary-foreground shadow-sm'
          : 'text-muted-foreground hover:text-foreground',
      )"
      @click="emit('update:modelValue', item.value)"
    >
      <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
      {{ item.label }}
    </button>
  </div>
</template>
