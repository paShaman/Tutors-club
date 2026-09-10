import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'

type Replace = Record<string, string | number>

const page = usePage<SharedProps>()

// Соответствие кода языка и BCP-47 локали для Intl (даты, числа, плюрализация)
const INTL_LOCALES: Record<string, string> = {
  ru: 'ru-RU',
  en: 'en-US',
}

export const locale = computed<string>(() => page.props.locale ?? 'ru')

export const intlLocale = computed<string>(() => INTL_LOCALES[locale.value] ?? locale.value)

function resolve(key: string): unknown {
  const dict = page.props.translations

  if (!dict) {
    return undefined
  }

  return key.split('.').reduce<unknown>((acc, part) => {
    if (acc && typeof acc === 'object' && part in (acc as Record<string, unknown>)) {
      return (acc as Record<string, unknown>)[part]
    }
    return undefined
  }, dict)
}

function interpolate(value: string, replace?: Replace): string {
  if (!replace) {
    return value
  }

  return Object.entries(replace).reduce(
    (str, [key, val]) => str.split(`:${key}`).join(String(val)),
    value,
  )
}

/**
 * Перевод по ключу из resources/lang/<locale>/messages.php.
 * Если ключ не найден — возвращает сам ключ (удобно замечать пропуски).
 */
export function t(key: string, replace?: Replace): string {
  const value = resolve(key)

  if (typeof value !== 'string') {
    return key
  }

  return interpolate(value, replace)
}

/**
 * Плюрализованный перевод. Ключ указывает на набор форм по категориям
 * Intl.PluralRules (one/few/many/other), например ui.students_count.one.
 */
export function tp(key: string, count: number, replace?: Replace): string {
  const category = new Intl.PluralRules(intlLocale.value).select(count)

  return t(`${key}.${category}`, { count, ...replace })
}

export function useI18n() {
  return { t, tp, locale, intlLocale }
}
