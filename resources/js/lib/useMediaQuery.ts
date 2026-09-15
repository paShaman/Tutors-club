import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue'

/**
 * Реактивная проверка media-запроса. Нужна, чтобы рендерить разное поведение
 * для десктопа и мобилки (например, кастомный и системный select).
 */
export function useMediaQuery(query: string): Ref<boolean> {
  const matches = ref(typeof window !== 'undefined' && window.matchMedia(query).matches)

  let mql: MediaQueryList | null = null

  function update(event: MediaQueryList | MediaQueryListEvent): void {
    matches.value = event.matches
  }

  onMounted(() => {
    mql = window.matchMedia(query)
    update(mql)
    mql.addEventListener('change', update)
  })

  onBeforeUnmount(() => {
    mql?.removeEventListener('change', update)
    mql = null
  })

  return matches
}
