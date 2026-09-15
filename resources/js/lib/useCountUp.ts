import { onBeforeUnmount, ref, watch, type Ref } from 'vue'

/**
 * Плавно «набегает» от текущего показанного значения к целевому при изменении
 * источника. Нужна для числовых плашек статистики: при первом появлении
 * значение бежит от нуля, при последующих изменениях — от прошлого числа.
 */
export function useCountUp(source: Ref<number | null | undefined>, duration = 900): Ref<number> {
  const display = ref(0)
  let frame = 0

  function stop(): void {
    if (frame) {
      cancelAnimationFrame(frame)
      frame = 0
    }
  }

  function run(to: number): void {
    stop()

    const from = display.value

    if (
      from === to
      || typeof window === 'undefined'
      || window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
      display.value = to
      return
    }

    const started = performance.now()

    const step = (now: number): void => {
      const progress = Math.min((now - started) / duration, 1)
      const eased = 1 - Math.pow(1 - progress, 3)

      display.value = from + (to - from) * eased

      if (progress < 1) {
        frame = requestAnimationFrame(step)
      } else {
        frame = 0
        display.value = to
      }
    }

    frame = requestAnimationFrame(step)
  }

  watch(source, (value) => run(Number(value) || 0), { immediate: true })
  onBeforeUnmount(stop)

  return display
}
