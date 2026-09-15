<script setup lang="ts">
import { computed, type Component } from 'vue'
import Card from './Card.vue'
import { cn } from '@/lib/utils'
import { useCountUp } from '@/lib/useCountUp'
import { useI18n } from '@/lib/i18n'

export type StatTone = 'primary' | 'emerald' | 'blue' | 'red' | 'amber' | 'violet'
export type StatFormat = 'number' | 'money' | 'percent'

interface Props {
  label: string
  value: number
  icon?: Component
  tone?: StatTone
  format?: StatFormat
  decimals?: number
  valueClass?: string
  hint?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  icon: undefined,
  tone: 'primary',
  format: 'number',
  decimals: 0,
  valueClass: '',
  hint: '',
  class: '',
})

const { intlLocale } = useI18n()

// Число плашки всегда анимируется само: при первом появлении набегает от нуля,
// при обновлении данных — от прошлого значения. Отдельно подключать не нужно.
const shown = useCountUp(computed(() => props.value))

const tones: Record<StatTone, { badge: string; icon: string }> = {
  primary: { badge: 'bg-primary/10',    icon: 'text-primary' },
  emerald: { badge: 'bg-emerald-500/10', icon: 'text-emerald-600' },
  blue:    { badge: 'bg-blue-500/10',    icon: 'text-blue-600' },
  red:     { badge: 'bg-red-500/10',     icon: 'text-red-600' },
  amber:   { badge: 'bg-amber-500/10',   icon: 'text-amber-600' },
  violet:  { badge: 'bg-violet-500/10',  icon: 'text-violet-600' },
}

const accent = computed(() => tones[props.tone])

const formatted = computed(() => {
  const value = props.decimals > 0 ? shown.value : Math.round(shown.value)

  const text = value.toLocaleString(intlLocale.value, {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals,
  })

  if (props.format === 'money') return `${text} ₽`
  if (props.format === 'percent') return `${text}%`

  return text
})
</script>

<template>
  <Card :class="cn('p-5', props.class)">
    <div class="flex items-center justify-between">
      <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          {{ label }}
        </p>
        <p :class="cn('mt-2 text-2xl font-bold whitespace-nowrap text-foreground xl:text-3xl', valueClass)">
          {{ formatted }}
        </p>
        <p v-if="hint" class="mt-1 text-xs text-muted-foreground">
          {{ hint }}
        </p>
      </div>
      <div :class="cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', accent.badge)">
        <slot name="icon">
          <component :is="icon" v-if="icon" :class="cn('h-5 w-5 shrink-0', accent.icon)" />
        </slot>
      </div>
    </div>
  </Card>
</template>
