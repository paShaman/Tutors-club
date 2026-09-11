<script setup lang="ts">
import { Shuffle } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { STUDENT_COLOR_CLASSES, STUDENT_COLOR_KEYS, randomStudentColor } from '@/lib/studentColors'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  modelValue?: string
}>(), {
  modelValue: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function select(color: string) {
  emit('update:modelValue', color)
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <button
      v-for="color in STUDENT_COLOR_KEYS"
      :key="color"
      type="button"
      class="h-8 w-8 cursor-pointer rounded-full bg-gradient-to-br ring-offset-2 ring-offset-background transition-transform hover:scale-110"
      :class="cn(
        STUDENT_COLOR_CLASSES[color],
        modelValue === color ? 'ring-2 ring-primary' : '',
      )"
      @click="select(color)"
    />
    <button
      type="button"
      class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition-all hover:border-foreground/20 hover:shadow-md"
      @click="select(randomStudentColor())"
    >
      <Shuffle class="h-3.5 w-3.5" />
      {{ t('ui.students.form.color_random') }}
    </button>
  </div>
</template>
