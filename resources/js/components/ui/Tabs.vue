<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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

const root = ref<HTMLElement | null>(null)
const buttons = ref<(HTMLButtonElement | null)[]>([])

// Позиция «плавающей» подложки активного таба относительно контейнера.
const indicator = ref({ left: 0, top: 0, width: 0, height: 0 })
// Плавность включаем только после первого позиционирования, чтобы подложка не «въезжала» с нуля.
const ready = ref(false)

const activeIndex = computed(() => props.items.findIndex((item) => item.value === props.modelValue))

function setButtonRef(el: Element | Component | null, index: number): void {
  buttons.value[index] = el instanceof HTMLButtonElement ? el : null
}

function updateIndicator(): void {
  const active = buttons.value[activeIndex.value]
  if (!active) return

  indicator.value = {
    left: active.offsetLeft,
    top: active.offsetTop,
    width: active.offsetWidth,
    height: active.offsetHeight,
  }
}

function scheduleUpdate(): void {
  nextTick(updateIndicator)
}

watch(() => props.modelValue, scheduleUpdate)
watch(() => props.items, scheduleUpdate, { deep: true })

let observer: ResizeObserver | null = null

onMounted(() => {
  scheduleUpdate()
  requestAnimationFrame(() => {
    ready.value = true
  })

  if (typeof ResizeObserver !== 'undefined' && root.value) {
    observer = new ResizeObserver(scheduleUpdate)
    observer.observe(root.value)
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  observer = null
})

const indicatorStyle = computed(() => ({
  left: `${indicator.value.left}px`,
  top: `${indicator.value.top}px`,
  width: `${indicator.value.width}px`,
  height: `${indicator.value.height}px`,
}))
</script>

<template>
  <div
    ref="root"
    :class="cn('relative inline-flex flex-wrap items-center rounded-xl border border-border bg-white/60 p-1', props.class)"
  >
    <span
      aria-hidden="true"
      :class="cn(
        'pointer-events-none absolute rounded-lg bg-primary shadow-sm',
        ready && 'transition-[left,top,width,height] duration-300 ease-out',
      )"
      :style="indicatorStyle"
    />
    <button
      v-for="(item, index) in props.items"
      :key="item.value"
      :ref="(el) => setButtonRef(el, index)"
      type="button"
      :class="cn(
        'relative z-10 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
        item.value === props.modelValue
          ? 'text-primary-foreground'
          : 'text-muted-foreground hover:text-foreground',
      )"
      @click="emit('update:modelValue', item.value)"
    >
      <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
      {{ item.label }}
    </button>
  </div>
</template>
