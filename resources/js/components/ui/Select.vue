<script setup lang="ts" generic="T extends SelectValue">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { useMediaQuery } from '@/lib/useMediaQuery'

export type SelectValue = string | number | null

export type SelectOption<V extends SelectValue = string> = {
  value: V
  label: string
  disabled?: boolean
}

const props = withDefaults(defineProps<{
  modelValue?: T
  options: SelectOption<T>[]
  placeholder?: string
  disabled?: boolean
  required?: boolean
  id?: string
  name?: string
  ariaLabel?: string
  class?: string
}>(), {
  placeholder: '',
  disabled: false,
  required: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: T]
}>()

// Десктоп — кастомный список, мобилка — системный select (нативные тапы и колесо выбора).
const isDesktop = useMediaQuery('(min-width: 1024px)')

const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)
const open = ref(false)
const activeIndex = ref(-1)

const selectedIndex = computed(() => props.options.findIndex((option) => option.value === props.modelValue))

const selectedLabel = computed(() => props.options[selectedIndex.value]?.label ?? '')

const triggerLabel = computed(() => selectedLabel.value || props.placeholder)

// Нативные `<select>` не умеют `null`-значение в опции «заглушки», поэтому подставляем пустую строку.
const nativeValue = computed(() => (props.modelValue === null || props.modelValue === undefined ? '' : props.modelValue))

const fieldClass = 'field w-full px-3.5 py-2.5'

function select(option: SelectOption<T>): void {
  if (option.disabled) return

  emit('update:modelValue', option.value)
  open.value = false
  nextTick(() => trigger.value?.focus())
}

function onNativeChange(event: Event): void {
  const value = (event.target as HTMLSelectElement).value
  // null-опция рендерится как пустое значение, поэтому сравниваем с учётом этого
  const option = props.options.find((item) => (item.value === null ? '' : String(item.value)) === value)
  emit('update:modelValue', (option ? option.value : value) as T)
}

function moveActive(step: number): void {
  if (!props.options.length) return

  let index = activeIndex.value

  for (let i = 0; i < props.options.length; i += 1) {
    index = (index + step + props.options.length) % props.options.length
    if (!props.options[index].disabled) {
      activeIndex.value = index
      return
    }
  }
}

function openList(): void {
  if (props.disabled || open.value) return

  open.value = true
  activeIndex.value = selectedIndex.value >= 0 ? selectedIndex.value : props.options.findIndex((option) => !option.disabled)
}

function toggleList(): void {
  if (open.value) {
    open.value = false
    return
  }

  openList()
}

function onKeydown(event: KeyboardEvent): void {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      if (open.value) moveActive(1)
      else openList()
      break
    case 'ArrowUp':
      event.preventDefault()
      if (open.value) moveActive(-1)
      else openList()
      break
    case 'Enter':
    case ' ':
      event.preventDefault()
      if (open.value) {
        const option = props.options[activeIndex.value]
        if (option) select(option)
      } else {
        openList()
      }
      break
    case 'Escape':
      open.value = false
      break
    case 'Tab':
      open.value = false
      break
  }
}

// Закрытие кастомного списка по клику снаружи
function onClickOutside(event: MouseEvent): void {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

// Реактивно держим подсветку на выбранном пункте при каждом открытии
watch(open, (value) => {
  if (value) activeIndex.value = selectedIndex.value
})

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="root" class="relative">
    <!-- Мобилка: системный select -->
    <select
      v-if="!isDesktop"
      :id="id"
      :class="cn(fieldClass, disabled && 'cursor-not-allowed opacity-50', props.class)"
      :name="name"
      :value="nativeValue"
      :disabled="disabled"
      :required="required"
      :aria-label="ariaLabel"
      @change="onNativeChange"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="String(option.value)"
        :value="option.value === null ? '' : option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>

    <!-- Десктоп: кастомный список -->
    <template v-else>
      <!-- Невидимый нативный select только для браузерной валидации required -->
      <select
        v-if="required"
        class="pointer-events-none absolute left-0 top-0 h-px w-px opacity-0"
        tabindex="-1"
        aria-hidden="true"
        required
        :value="nativeValue"
        @change="onNativeChange"
      >
        <option value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="String(option.value)"
          :value="option.value === null ? '' : option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <button
        ref="trigger"
        type="button"
        :id="id"
        :class="cn(
          fieldClass,
          'flex cursor-pointer items-center justify-between gap-2 text-left',
          disabled && 'cursor-not-allowed opacity-50',
          props.class,
        )"
        :disabled="disabled"
        :aria-label="ariaLabel"
        aria-haspopup="listbox"
        :aria-required="required"
        :aria-expanded="open"
        @click="toggleList"
        @keydown="onKeydown"
      >
        <span :class="cn('truncate', !selectedLabel && 'text-muted-foreground')">
          {{ triggerLabel }}
        </span>
        <ChevronDown
          class="h-4 w-4 shrink-0 text-muted-foreground transition-transform"
          :class="open && 'rotate-180'"
        />
      </button>

      <Transition name="dropdown">
        <div
          v-if="open"
          role="listbox"
          class="absolute z-50 mt-1.5 max-h-60 w-full overflow-y-auto rounded-xl border border-border bg-popover p-1 shadow-lg"
        >
          <button
            v-for="(option, index) in options"
            :key="String(option.value)"
            type="button"
            role="option"
            :aria-selected="option.value === modelValue"
            :disabled="option.disabled"
            :class="cn(
              'flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors',
              option.disabled && 'cursor-not-allowed opacity-50',
              index === activeIndex && !option.disabled && 'bg-accent text-accent-foreground',
              option.value === modelValue ? 'font-medium text-primary' : 'text-foreground',
            )"
            @mouseenter="activeIndex = index"
            @click="select(option)"
          >
            <span class="truncate">{{ option.label }}</span>
            <Check v-if="option.value === modelValue" class="h-4 w-4 shrink-0" />
          </button>
        </div>
      </Transition>
    </template>
  </div>
</template>
