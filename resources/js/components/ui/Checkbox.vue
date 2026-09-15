<script setup lang="ts">
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  modelValue?: boolean
  disabled?: boolean
  class?: string
}>(), {
  modelValue: false,
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

function onChange(): void {
  if (props.disabled) return
  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <label
    :class="cn(
      'inline-flex cursor-pointer select-none items-center gap-2',
      disabled && 'cursor-not-allowed opacity-50',
      props.class,
    )"
  >
    <input
      type="checkbox"
      class="peer sr-only"
      :checked="modelValue"
      :disabled="disabled"
      @change="onChange"
    />
    <span
      aria-hidden="true"
      :class="cn(
        'flex h-5 w-5 shrink-0 items-center justify-center rounded-md border bg-white/50 transition-colors',
        'peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30',
        modelValue ? 'border-primary bg-primary text-primary-foreground' : 'border-border',
      )"
    >
      <Check
        class="h-3.5 w-3.5 transition-opacity"
        :class="modelValue ? 'opacity-100' : 'opacity-0'"
      />
    </span>
    <span v-if="$slots.default" class="text-sm text-foreground"><slot /></span>
  </label>
</template>
