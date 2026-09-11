import { reactive } from 'vue'

export type ToastVariant = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: number
  message: string
  variant: ToastVariant
  /** Время автозакрытия в мс; 0 — закрыть можно только вручную */
  duration: number
}

export interface ToastOptions {
  duration?: number
}

const DEFAULT_DURATION: Record<ToastVariant, number> = {
  success: 4000,
  info: 4000,
  warning: 6000,
  // Ошибка закрывается сама, но висит в 3 раза дольше обычного тоста
  error: 12000,
}

// Ограничение стека тостов: на мобильных меньше, чтобы не перекрывать контент
const MOBILE_LIMIT = 3
const DESKTOP_LIMIT = 5

const toasts = reactive<Toast[]>([])
let nextId = 1

function toastLimit(): number {
  if (typeof window === 'undefined') {
    return DESKTOP_LIMIT
  }

  return window.matchMedia('(min-width: 640px)').matches ? DESKTOP_LIMIT : MOBILE_LIMIT
}

function push(message: string, variant: ToastVariant, duration?: number): number {
  // Дубликаты не копим: старый такой же тост убираем, чтобы активным был только новый
  for (let i = toasts.length - 1; i >= 0; i--) {
    if (toasts[i].message === message && toasts[i].variant === variant) {
      toasts.splice(i, 1)
    }
  }

  const id = nextId++

  toasts.push({
    id,
    message,
    variant,
    duration: duration ?? DEFAULT_DURATION[variant],
  })

  while (toasts.length > toastLimit()) {
    toasts.shift()
  }

  return id
}

export function dismissToast(id: number): void {
  const index = toasts.findIndex((toast) => toast.id === id)

  if (index !== -1) {
    toasts.splice(index, 1)
  }
}

export function useToast() {
  return {
    toasts,
    success: (message: string, options?: ToastOptions) => push(message, 'success', options?.duration),
    error: (message: string, options?: ToastOptions) => push(message, 'error', options?.duration),
    warning: (message: string, options?: ToastOptions) => push(message, 'warning', options?.duration),
    info: (message: string, options?: ToastOptions) => push(message, 'info', options?.duration),
    dismiss: dismissToast,
  }
}
