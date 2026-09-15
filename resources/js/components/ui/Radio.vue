<script setup lang="ts">
import { cn } from '@/lib/utils'

type RadioValue = string | number | boolean

const props = withDefaults(defineProps<{
  modelValue?: RadioValue
  value: RadioValue
  name?: string
  disabled?: boolean
  class?: string
}>(), {
  modelValue: '',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: RadioValue]
}>()

function onChange(): void {
  if (props.disabled) return
  emit('update:modelValue', props.value)
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
      type="radio"
      class="peer sr-only"
      :name="name"
      :value="value"
      :checked="modelValue === value"
      :disabled="disabled"
      @change="onChange"
    />
    <span
      aria-hidden="true"
      :class="cn(
        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border bg-white/50 transition-colors',
        'peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30',
        modelValue === value ? 'border-primary' : 'border-border',
      )"
    >
      <span
        class="h-2.5 w-2.5 rounded-full bg-primary transition-transform"
        :class="modelValue === value ? 'scale-100' : 'scale-0'"
      />
    </span>
    <span v-if="$slots.default" class="text-sm text-foreground"><slot /></span>
  </label>
</template>
