import { watch, onUnmounted } from 'vue'

// Счётчик, потому что попапы могут открываться друг поверх друга (например, тема внутри урока).
let lockCount = 0
let savedOverflow = ''
let savedPaddingRight = ''

function lock(): void {
  if (lockCount === 0) {
    // Компенсируем исчезающий скроллбар, чтобы страница не «прыгала» вбок.
    const scrollbar = window.innerWidth - document.documentElement.clientWidth

    savedOverflow = document.body.style.overflow
    savedPaddingRight = document.body.style.paddingRight
    document.body.style.overflow = 'hidden'

    if (scrollbar > 0) {
      document.body.style.paddingRight = `${scrollbar}px`
    }
  }

  lockCount++
}

function unlock(): void {
  if (lockCount === 0) {
    return
  }

  lockCount--

  if (lockCount === 0) {
    document.body.style.overflow = savedOverflow
    document.body.style.paddingRight = savedPaddingRight
  }
}

/**
 * Блокирует прокрутку страницы, пока попап открыт.
 * Принимает геттер, чтобы работать и с `props.show`, и с любым вычисляемым условием.
 */
export function useScrollLock(active: () => boolean): void {
  let locked = false

  const sync = (value: boolean): void => {
    if (value === locked) {
      return
    }

    locked = value

    if (value) {
      lock()
    } else {
      unlock()
    }
  }

  watch(active, sync, { immediate: true })
  onUnmounted(() => sync(false))
}
