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

const toasts = reactive<Toast[]>([])
let nextId = 1

function push(message: string, variant: ToastVariant, duration?: number): number {
  const id = nextId++

  toasts.push({
    id,
    message,
    variant,
    duration: duration ?? DEFAULT_DURATION[variant],
  })

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
