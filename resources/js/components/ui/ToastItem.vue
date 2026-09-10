<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, type Component } from 'vue'
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from 'lucide-vue-next'
import type { Toast, ToastVariant } from '@/lib/toast'
import { dismissToast } from '@/lib/toast'
import { cn } from '@/lib/utils'

const props = defineProps<{ toast: Toast }>()

const icons: Record<ToastVariant, Component> = {
  success: CheckCircle2,
  error: XCircle,
  warning: AlertTriangle,
  info: Info,
}

const variantClasses: Record<ToastVariant, string> = {
  success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  error: 'border-destructive/30 bg-destructive/10 text-destructive',
  warning: 'border-amber-200 bg-amber-50 text-amber-800',
  info: 'border-blue-200 bg-blue-50 text-blue-800',
}

const icon = computed(() => icons[props.toast.variant])

// Таймер останавливается на hover/focus, чтобы тост не исчез под курсором
const remaining = ref(props.toast.duration)
let timer: number | null = null
let startedAt = 0

function clearTimer(): void {
  if (timer !== null) {
    window.clearTimeout(timer)
    timer = null
  }
}

function startTimer(): void {
  if (remaining.value <= 0 || timer !== null) {
    return
  }

  startedAt = Date.now()
  timer = window.setTimeout(() => dismissToast(props.toast.id), remaining.value)
}

function pauseTimer(): void {
  if (timer === null) {
    return
  }

  window.clearTimeout(timer)
  timer = null
  remaining.value -= Date.now() - startedAt
}

onMounted(startTimer)
onBeforeUnmount(clearTimer)
</script>

<template>
  <div
    :class="cn(
      'pointer-events-auto flex w-full items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur',
      variantClasses[toast.variant],
    )"
    :role="toast.variant === 'error' ? 'alert' : 'status'"
    :aria-live="toast.variant === 'error' ? 'assertive' : 'polite'"
    @mouseenter="pauseTimer"
    @mouseleave="startTimer"
    @focusin="pauseTimer"
    @focusout="startTimer"
  >
    <component :is="icon" class="mt-0.5 h-5 w-5 shrink-0" />

    <p class="flex-1 whitespace-pre-line break-words text-sm">{{ toast.message }}</p>

    <button
      type="button"
      class="-mr-1 shrink-0 rounded-lg p-1 opacity-60 transition-opacity hover:opacity-100 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current"
      aria-label="Закрыть"
      @click="dismissToast(toast.id)"
    >
      <X class="h-4 w-4" />
    </button>
  </div>
</template>
