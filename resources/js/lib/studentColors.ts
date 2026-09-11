// Палитра цветов аватарки ученика. Ключи должны совпадать с App\Model\Student::COLORS.
export const STUDENT_COLOR_CLASSES: Record<string, string> = {
  red: 'from-red-500 to-rose-600',
  orange: 'from-orange-500 to-amber-600',
  amber: 'from-amber-500 to-orange-600',
  yellow: 'from-yellow-500 to-amber-600',
  lime: 'from-lime-500 to-green-600',
  green: 'from-green-500 to-emerald-600',
  emerald: 'from-emerald-500 to-teal-600',
  teal: 'from-teal-500 to-cyan-600',
  cyan: 'from-cyan-500 to-sky-600',
  sky: 'from-sky-500 to-blue-600',
  blue: 'from-blue-500 to-indigo-600',
  indigo: 'from-indigo-500 to-violet-600',
  violet: 'from-violet-500 to-purple-600',
  purple: 'from-purple-500 to-fuchsia-600',
  fuchsia: 'from-fuchsia-500 to-pink-600',
  pink: 'from-pink-500 to-rose-600',
  rose: 'from-rose-500 to-red-600',
  slate: 'from-slate-500 to-slate-700',
  gray: 'from-gray-500 to-gray-700',
  zinc: 'from-zinc-500 to-zinc-700',
  neutral: 'from-neutral-500 to-neutral-700',
  stone: 'from-stone-500 to-stone-700',
}

export const STUDENT_COLOR_KEYS = Object.keys(STUDENT_COLOR_CLASSES)

const FALLBACK_COLOR = 'violet'

/**
 * Tailwind-класс градиента по ключу цвета. Неизвестный/пустой цвет — безопасный запасной.
 */
export function studentColorClass(color?: string | null): string {
  if (color && STUDENT_COLOR_CLASSES[color]) {
    return STUDENT_COLOR_CLASSES[color]
  }

  return STUDENT_COLOR_CLASSES[FALLBACK_COLOR]
}

/**
 * Случайный ключ цвета из палитры (используется как значение по умолчанию).
 */
export function randomStudentColor(): string {
  return STUDENT_COLOR_KEYS[Math.floor(Math.random() * STUDENT_COLOR_KEYS.length)]
}
