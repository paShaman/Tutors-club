<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import IconButton from '@/components/ui/IconButton.vue'
import Select from '@/components/ui/Select.vue'
import type { SelectOption } from '@/components/ui/Select.vue'
import { ref, computed, onMounted, watch } from 'vue'
import { cn } from '@/lib/utils'
import {
  BookOpen,
  Plus,
  Pencil,
  Trash2,
  Coins,
  Clock,
  ChevronDown,
  ChevronRight,
  Timer,
  Star,
  FilterX,
  Loader2,
} from 'lucide-vue-next'
import LessonFormPopup from '@/components/popups/LessonFormPopup.vue'
import type { LessonFormData, TopicNode } from '@/components/popups/LessonFormPopup.vue'
import StudentAvatar from '@/components/ui/StudentAvatar.vue'
import TopicStatusBadge from '@/components/ui/TopicStatusBadge.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import type { TariffInfo } from '@/types'

defineOptions({ layout: AppLayout })

interface Lesson {
  id: number
  student_id: number
  subject: string
  topic_id: number | null
  subtopic_id: number | null
  topic_name: string | null
  subtopic_name: string | null
  comment: string | null
  price: number
  duration: number
  is_payed: number
  date: string
  date_payed: string | null
  time: string | null
  is_future: number
}

interface StudentData {
  id: number
  name: string
  class: string | null
  current_class: string
  type: string | null
  gender: string
  color: string | null
}

interface StudentGroup {
  student: StudentData
  lessons: Lesson[]
  sum: number
  sum_not_payed: number
  sum_special: number
  cnt: number
  cnt_not_payed: number
  cnt_special: number
  cnt_all: number
}

interface MonthGroup {
  open: boolean
  students: Record<number, StudentGroup>
  sum: number
  sum_not_payed: number
  sum_special: number
  cnt: number
  cnt_not_payed: number
  cnt_special: number
  cnt_all: number
}

interface YearGroup {
  open: boolean
  months: Record<number, MonthGroup>
  sum: number
  sum_not_payed: number
  sum_special: number
  cnt: number
  cnt_not_payed: number
  cnt_special: number
  cnt_all: number
}

type LessonsPageProps = {
  sortedLessons: Record<number, YearGroup>
  students: StudentData[]
  selectedStudentId: number | null
  selectedSubject: string | null
  lessonsSubjects: string[]
  subjectNames: Record<string, string>
  subjectSlugs: Record<string, string>
  studentSlugs: Record<number, string>
  topicTree: Record<string, TopicNode[]>
  topicStatuses: Record<number, Record<number, string>>
  defaultPrice: number
  defaultDuration: number
  defaultDate: string
  tariff: TariffInfo | null
}

const page = usePage<LessonsPageProps>()

const toast = useToast()
const { t, tp, intlLocale } = useI18n()

const canAddLesson = computed(() => page.props.tariff?.can.lessons ?? true)

const selectedStudentId = ref<number | null>(page.props.selectedStudentId ?? null)
const selectedSubject = ref<string | null>(page.props.selectedSubject ?? null)

const studentFilterOptions = computed<SelectOption<number | null>[]>(() => [
  { value: null, label: t('ui.lessons.all_students') },
  ...page.props.students.map((student) => ({
    value: student.id,
    label: student.current_class ? `${student.name} (${student.current_class})` : student.name,
  })),
])

const subjectFilterOptions = computed<SelectOption<string | null>[]>(() => [
  { value: null, label: t('ui.lessons.all_subjects') },
  ...page.props.lessonsSubjects.map((subject) => ({ value: subject, label: subjectName(subject) })),
])

function applyFilters() {
  const studentSlug = selectedStudentId.value
    ? page.props.studentSlugs?.[selectedStudentId.value]
    : null
  const subjectSlug = selectedSubject.value
    ? page.props.subjectSlugs?.[selectedSubject.value]
    : null

  let url = '/lessons'
  if (studentSlug && subjectSlug) {
    url = `/lessons/students/${studentSlug}/subjects/${subjectSlug}`
  } else if (studentSlug) {
    url = `/lessons/students/${studentSlug}`
  } else if (subjectSlug) {
    url = `/lessons/subjects/${subjectSlug}`
  }

  router.get(url, {}, {
    preserveState: true,
    preserveScroll: true,
    only: ['sortedLessons', 'selectedStudentId', 'selectedSubject'],
    onStart: () => { isFiltering.value = true },
    onFinish: () => { isFiltering.value = false },
  })
}

watch([selectedStudentId, selectedSubject], applyFilters)

function clearFilters() {
  selectedStudentId.value = null
  selectedSubject.value = null
}

const hasFilters = computed(() => selectedStudentId.value !== null || selectedSubject.value !== null)

// Смена фильтра — это обычный сетевой переход (only), а выглядит как локальный фильтр:
// пока список остаётся прежним, показываем, что он обновляется.
const isFiltering = ref(false)

const monthNames = computed(() => {
  const formatter = new Intl.DateTimeFormat(intlLocale.value, { month: 'long' })
  const names = ['']
  for (let m = 0; m < 12; m++) {
    const name = formatter.format(new Date(2020, m, 1))
    names.push(name.charAt(0).toUpperCase() + name.slice(1))
  }
  return names
})

function subjectName(key: string): string {
  return page.props.subjectNames?.[key] ?? t(key)
}

function lessonTopicStatus(lesson: Lesson): string {
  const topicId = lesson.subtopic_id ?? lesson.topic_id
  if (!topicId) return ''
  return page.props.topicStatuses?.[lesson.student_id]?.[topicId] ?? ''
}

const years = computed(() => {
  return Object.entries(page.props.sortedLessons ?? {})
    .map(([year, data]) => ({ year: Number(year), ...data }))
    .sort((a, b) => b.year - a.year)
})

const yearVisibility = ref<Record<number, boolean>>({})
const monthVisibility = ref<Record<string, boolean>>({})
const studentVisibility = ref<Record<string, boolean>>({})

onMounted(() => {
  const now = new Date()
  const currentYear = now.getFullYear()
  const currentMonth = now.getMonth() + 1

  yearVisibility.value[currentYear] = true

  const monthKey = `${currentYear}-${currentMonth}`
  monthVisibility.value[monthKey] = true

  const yearData = page.props.sortedLessons?.[currentYear]
  const monthData = yearData?.months?.[currentMonth]
  if (monthData?.students) {
    for (const studentId of Object.keys(monthData.students)) {
      studentVisibility.value[`${currentYear}-${currentMonth}-${studentId}`] = true
    }
  }
})

function toggleYear(year: number) {
  yearVisibility.value[year] = !yearVisibility.value[year]
}

function toggleMonth(year: number, month: number) {
  const key = `${year}-${month}`
  monthVisibility.value[key] = !monthVisibility.value[key]
}

function toggleStudent(year: number, month: number, studentId: number) {
  const key = `${year}-${month}-${studentId}`
  studentVisibility.value[key] = !studentVisibility.value[key]
}

// Popup state
const showLessonPopup = ref(false)
const lessonPopupMode = ref<'add' | 'edit'>('add')
const lessonPopupInitial = ref<LessonFormData | null>(null)

// Confirm dialog state
const showConfirm = ref(false)
const confirmMessage = ref('')
const confirmVariant = ref<'danger' | 'default'>('danger')
let confirmCallback: (() => void) | null = null

function openConfirm(message: string, variant: 'danger' | 'default', callback: () => void) {
  confirmMessage.value = message
  confirmVariant.value = variant
  confirmCallback = callback
  showConfirm.value = true
}

function onConfirm() {
  showConfirm.value = false
  if (confirmCallback) confirmCallback()
  confirmCallback = null
}

function onCancel() {
  showConfirm.value = false
  confirmCallback = null
}

function requestAddLesson() {
  if (!canAddLesson.value) {
    toast.warning(t('ui.tariff.limit.lessons'))
    return
  }
  openAddModal()
}

function requestAddLessonForStudent(studentGroup: StudentGroup) {
  if (!canAddLesson.value) {
    toast.warning(t('ui.tariff.limit.lessons'))
    return
  }
  openAddModalForStudent(studentGroup)
}

function openAddModal() {
  lessonPopupMode.value = 'add'

  const now = new Date()
  const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')
  const previousHour = (now.getHours() - 1 + 24) % 24
  const roundedHour = String(previousHour).padStart(2, '0') + ':00'

  lessonPopupInitial.value = {
    lesson_id: null,
    lesson_student_id: '',
    lesson_subject: '',
    lesson_topic_id: null,
    lesson_subtopic_id: null,
    lesson_topic_status: 'in_progress',
    lesson_comment: '',
    lesson_price: page.props.defaultPrice,
    lesson_duration: page.props.defaultDuration,
    lesson_date: todayStr,
    lesson_time: roundedHour,
    lesson_date_payed: '',
    lesson_is_payed: false,
    lesson_is_future: false,
  }
  showLessonPopup.value = true
}

function openAddModalForStudent(studentGroup: StudentGroup) {
  lessonPopupMode.value = 'add'

  const sortedLessons = [...studentGroup.lessons].sort(
    (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime() || (b.time ?? '').localeCompare(a.time ?? '')
  )
  const lastLesson = sortedLessons[0] ?? null

  const now = new Date()
  const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0')
  const previousHour = (now.getHours() - 1 + 24) % 24
  const roundedHour = String(previousHour).padStart(2, '0') + ':00'

  lessonPopupInitial.value = {
    lesson_id: null,
    lesson_student_id: String(studentGroup.student.id),
    lesson_subject: lastLesson?.subject ?? '',
    lesson_topic_id: lastLesson?.topic_id ?? null,
    lesson_subtopic_id: lastLesson?.subtopic_id ?? null,
    lesson_topic_status: 'in_progress',
    lesson_comment: '',
    lesson_price: lastLesson?.price ?? page.props.defaultPrice,
    lesson_duration: lastLesson?.duration ?? page.props.defaultDuration,
    lesson_date: todayStr,
    lesson_time: roundedHour,
    lesson_date_payed: '',
    lesson_is_payed: false,
    lesson_is_future: false,
  }
  showLessonPopup.value = true
}

function openEditModal(lesson: Lesson) {
  lessonPopupMode.value = 'edit'
  lessonPopupInitial.value = {
    lesson_id: lesson.id,
    lesson_student_id: String(lesson.student_id),
    lesson_subject: lesson.subject,
    lesson_topic_id: lesson.topic_id,
    lesson_subtopic_id: lesson.subtopic_id,
    lesson_topic_status: 'in_progress',
    lesson_comment: lesson.comment ?? '',
    lesson_price: lesson.price,
    lesson_duration: lesson.duration,
    lesson_date: lesson.date,
    lesson_time: lesson.time ?? '',
    lesson_date_payed: lesson.date_payed ? lesson.date_payed.substring(0, 10) : '',
    lesson_is_payed: !!lesson.is_payed,
    lesson_is_future: !!lesson.is_future,
  }
  showLessonPopup.value = true
}

function closeLessonPopup() {
  showLessonPopup.value = false
}

function handleLessonSubmit(form: LessonFormData) {
  router.post('/lessons/edit', form, {
    preserveScroll: true,
    onSuccess: () => closeLessonPopup(),
    onError: (errors) => toast.error(Object.values(errors).join('\n')),
  })
}

function handleLessonDelete() {
  if (!lessonPopupInitial.value?.lesson_id) return
  deleteLesson(lessonPopupInitial.value.lesson_id)
}

function deleteLesson(lessonId: number) {
  openConfirm(t('ui.lessons.delete_confirm'), 'danger', () => {
    router.post('/lessons/delete', { lesson_id: lessonId }, {
      preserveScroll: true,
      onSuccess: () => closeLessonPopup(),
      onError: (errors) => toast.error(Object.values(errors).join('\n')),
    })
  })
}

function pad2(value: number): string {
  return String(value).padStart(2, '0')
}

/** Текущий момент в том же формате, что отдаёт сервер в date_payed. */
function nowAsDateTime(): string {
  const now = new Date()
  return `${now.getFullYear()}-${pad2(now.getMonth() + 1)}-${pad2(now.getDate())} ` +
    `${pad2(now.getHours())}:${pad2(now.getMinutes())}:${pad2(now.getSeconds())}`
}

/**
 * Оптимистичная отметка «оплачен»: правит урок и те же суммы/счётчики, что
 * считает LessonController (будущие уроки и специальные ученики в суммы не входят).
 */
function markLessonPaid(props: LessonsPageProps, lessonId: number): Partial<LessonsPageProps> {
  const paidAt = nowAsDateTime()
  let changed = false

  const tree: Record<number, YearGroup> = {}

  for (const [yearKey, year] of Object.entries(props.sortedLessons ?? {})) {
    let yearSum = year.sum
    let yearSumNotPayed = year.sum_not_payed
    let yearCnt = year.cnt
    let yearCntNotPayed = year.cnt_not_payed

    const months: Record<number, MonthGroup> = {}

    for (const [monthKey, month] of Object.entries(year.months ?? {})) {
      let monthSum = month.sum
      let monthSumNotPayed = month.sum_not_payed
      let monthCnt = month.cnt
      let monthCntNotPayed = month.cnt_not_payed

      const students: Record<number, StudentGroup> = {}

      for (const [studentKey, group] of Object.entries(month.students ?? {})) {
        const index = group.lessons.findIndex((lesson) => lesson.id === lessonId)

        if (index === -1) {
          students[Number(studentKey)] = group
          continue
        }

        const lesson = group.lessons[index]
        const lessons = [...group.lessons]
        lessons[index] = { ...lesson, is_payed: 1, date_payed: paidAt }

        let sum = group.sum
        let sumNotPayed = group.sum_not_payed
        let cnt = group.cnt
        let cntNotPayed = group.cnt_not_payed

        if (!lesson.is_future && !group.student.type) {
          sum += lesson.price
          cnt += 1
          sumNotPayed -= lesson.price
          cntNotPayed -= 1

          monthSum += lesson.price
          monthCnt += 1
          monthSumNotPayed -= lesson.price
          monthCntNotPayed -= 1

          yearSum += lesson.price
          yearCnt += 1
          yearSumNotPayed -= lesson.price
          yearCntNotPayed -= 1
        }

        students[Number(studentKey)] = {
          ...group,
          lessons,
          sum,
          sum_not_payed: sumNotPayed,
          cnt,
          cnt_not_payed: cntNotPayed,
        }
        changed = true
      }

      months[Number(monthKey)] = {
        ...month,
        students,
        sum: monthSum,
        sum_not_payed: monthSumNotPayed,
        cnt: monthCnt,
        cnt_not_payed: monthCntNotPayed,
      }
    }

    tree[Number(yearKey)] = {
      ...year,
      months,
      sum: yearSum,
      sum_not_payed: yearSumNotPayed,
      cnt: yearCnt,
      cnt_not_payed: yearCntNotPayed,
    }
  }

  return changed ? { sortedLessons: tree } : {}
}

function togglePayLesson(lessonId: number) {
  router.post('/lessons/pay', { lesson_id: lessonId }, {
    preserveScroll: true,
    optimistic: (props) => markLessonPaid(props as unknown as LessonsPageProps, lessonId),
    onError: (errors) => toast.error(Object.values(errors).join('\n')),
  })
}

function sortedStudents(students: Record<number, StudentGroup>): StudentGroup[] {
  return Object.values(students).sort((a, b) => {
    const getLastDate = (g: StudentGroup) => g.lessons.reduce((max, l) => {
      const t = new Date(l.date).getTime()
      return t > max ? t : max
    }, 0)
    return getLastDate(b) - getLastDate(a)
  })
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString(intlLocale.value, { day: 'numeric', month: 'long' })
}

function formatDatePayed(dateStr: string | null): string {
  if (!dateStr) return t('ui.lessons.not_paid')
  const d = new Date(dateStr)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()

  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')

  if (hours == '00' && minutes == '00') {
      return `${day}.${month}.${year}`
  } else {
      return `${day}.${month}.${year} ${hours}:${minutes}`
  }
}
</script>

<template>
  <Head :title="t('ui.lessons.title')" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500/10">
          <BookOpen class="h-6 w-6 text-violet-600" />
        </div>
        <div>
          <h1 class="page-title text-2xl">
            {{ t('ui.lessons.title') }}
          </h1>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ t('ui.lessons.subtitle') }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap justify-end">
        <Select
          v-model="selectedStudentId"
          :options="studentFilterOptions"
          :aria-label="t('ui.lessons.filter_label')"
          class="w-auto py-2 min-w-[170px] sm:min-w-[200px]"
        />

        <Select
          v-model="selectedSubject"
          :options="subjectFilterOptions"
          :aria-label="t('ui.lessons.subject_filter_label')"
          class="w-auto py-2 min-w-[150px] sm:min-w-[180px]"
        />

        <button
          @click="clearFilters"
          :disabled="!hasFilters"
          class="field inline-flex h-[38px] w-[38px] items-center justify-center text-muted-foreground transition-colors hover:text-foreground disabled:opacity-35 disabled:cursor-default cursor-pointer"
          :title="t('ui.lessons.reset_filter')"
          :aria-label="t('ui.lessons.reset_filter')"
        >
          <FilterX class="h-4 w-4" />
        </button>

        <Button @click="requestAddLesson">
          <Plus class="h-4 w-4" />
          <span class="hidden sm:inline">{{ t('ui.lessons.add') }}</span>
        </Button>
      </div>
    </div>

    <!-- Years -->
    <div
      v-if="years.length"
      :class="cn('relative space-y-4 transition-opacity duration-200', isFiltering && 'opacity-60 pointer-events-none delay-200')"
      :aria-busy="isFiltering"
    >
      <!-- Обновление списка после смены фильтра: оверлей без v-if, чтобы появление
           тоже шло с задержкой delay-200 и не мигало на быстрых ответах -->
      <div
        class="pointer-events-none absolute inset-0 z-10 flex items-start justify-center pt-16 transition-opacity duration-200"
        :class="isFiltering ? 'opacity-100 delay-200' : 'opacity-0'"
        aria-hidden="true"
      >
        <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
      </div>

      <div v-for="yearData in years" :key="yearData.year">
        <!-- Year header -->
        <button
          @click="toggleYear(yearData.year)"
          class="w-full flex items-start lg:items-center gap-3 rounded-2xl bg-gradient-to-r from-primary/5 to-purple-500/5 border border-primary/10 px-5 py-4 hover:from-primary/10 hover:to-purple-500/10 transition-colors cursor-pointer"
        >
          <component
            :is="yearVisibility[yearData.year] ? ChevronDown : ChevronRight"
            class="h-5 w-5 text-primary shrink-0 mt-0.5 lg:mt-0 transition-transform duration-300"
          />
              <div class="flex-1 min-w-0 text-left">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
                  <div class="flex items-center gap-2.5 flex-wrap text-left">
                    <h2 class="text-xl font-bold text-foreground">{{ yearData.year }}</h2>
                    <p class="text-sm text-muted-foreground">
                      {{ tp('ui.lessons.count', yearData.cnt_all + yearData.cnt_special) }}
                      <span v-if="yearData.cnt_special" class="text-amber-600"> {{ t('ui.lessons.special_count', { count: yearData.cnt_special }) }}</span>
                    </p>
                  </div>
              <div class="flex items-center gap-2 flex-wrap shrink-0">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                  {{ (yearData.sum + yearData.sum_special).toLocaleString(intlLocale) }} ₽
                </span>
                <span v-if="yearData.sum_special" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-sm font-semibold text-amber-800">
                  <Star class="h-3.5 w-3.5 fill-amber-500 text-amber-600" />
                  {{ yearData.sum_special.toLocaleString(intlLocale) }} ₽
                </span>
                <span v-if="yearData.sum_not_payed" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-sm font-semibold text-red-600">
                  {{ t('ui.lessons.debt') }} {{ yearData.sum_not_payed.toLocaleString(intlLocale) }} ₽
                </span>
              </div>
            </div>
          </div>
        </button>

        <!-- Months (visible when year expanded) -->
        <Transition name="collapse">
          <div v-if="yearVisibility[yearData.year]" key="year" class="ml-3 mt-2 space-y-3">
            <div
              v-for="[monthNum, monthData] in Object.entries(yearData.months).sort((a, b) => Number(b[0]) - Number(a[0]))"
              :key="monthNum"
            >
              <!-- Month header -->
              <button
                @click="toggleMonth(yearData.year, Number(monthNum))"
                class="w-full flex items-start lg:items-center gap-2 rounded-xl bg-white/50 border border-border px-4 py-3 hover:bg-accent transition-colors cursor-pointer"
              >
                <component
                  :is="monthVisibility[`${yearData.year}-${monthNum}`] ? ChevronDown : ChevronRight"
                  class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5 lg:mt-0 transition-transform duration-300"
                />
                <div class="flex-1 min-w-0 text-left">
                  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1.5">
                    <div class="flex items-center gap-2.5 flex-wrap text-left">
                      <h3 class="font-semibold text-foreground">
                        {{ monthNames[Number(monthNum)] }}
                      </h3>
                      <p class="text-xs text-muted-foreground">
                        {{ tp('ui.lessons.count', monthData.cnt_all + monthData.cnt_special) }}
                        <span v-if="monthData.cnt_special" class="text-amber-600"> {{ t('ui.lessons.special_count', { count: monthData.cnt_special }) }}</span>
                      </p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                        {{ (monthData.sum + monthData.sum_special).toLocaleString(intlLocale) }} ₽
                      </span>
                      <span v-if="monthData.sum_special" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-xs font-semibold text-amber-800">
                        <Star class="h-3 w-3 fill-amber-500 text-amber-600" />
                        {{ monthData.sum_special.toLocaleString(intlLocale) }} ₽
                      </span>
                      <span v-if="monthData.sum_not_payed" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-xs font-semibold text-red-600">
                        {{ t('ui.lessons.debt') }} {{ monthData.sum_not_payed.toLocaleString(intlLocale) }} ₽
                      </span>
                    </div>
                  </div>
                </div>
              </button>

              <!-- Students (visible when month expanded) -->
              <Transition name="collapse">
                <div v-if="monthVisibility[`${yearData.year}-${monthNum}`]" key="month" class="mt-2 space-y-3">
                  <div v-for="studentGroup in sortedStudents(monthData.students)" :key="studentGroup.student.id">
                    <!-- Student header (collapsible) -->
                    <button
                      @click="toggleStudent(yearData.year, Number(monthNum), studentGroup.student.id)"
                      class="ml-3 w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-accent transition-colors cursor-pointer text-left"
                    >
                      <component
                        :is="studentVisibility[`${yearData.year}-${monthNum}-${studentGroup.student.id}`] ? ChevronDown : ChevronRight"
                        class="h-4 w-4 text-muted-foreground shrink-0 transition-transform duration-300"
                      />
                      <StudentAvatar
                        :name="studentGroup.student.name"
                        :gender="studentGroup.student.gender"
                        :color="studentGroup.student.color"
                        class="h-8 w-8"
                      />
                      <div class="flex-1 min-w-0 flex flex-col lg:flex-row lg:items-center gap-1.5 lg:gap-3">
                        <p class="text-sm font-medium text-foreground truncate">
                          {{ studentGroup.student.name }}
                          <span v-if="studentGroup.student.current_class" class="text-muted-foreground font-normal">
                            · {{ studentGroup.student.current_class }}
                          </span>
                        </p>
                        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                            {{ (studentGroup.sum + studentGroup.sum_special).toLocaleString(intlLocale) }} ₽
                          </span>
                          <span v-if="studentGroup.sum_special" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-xs font-semibold text-amber-700">
                            <Star class="h-3 w-3" />
                            {{ studentGroup.sum_special.toLocaleString(intlLocale) }} ₽
                          </span>
                          <span v-if="studentGroup.sum_not_payed" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-xs font-semibold text-red-600">
                            <span class="hidden sm:inline">{{ t('ui.lessons.debt') }} </span>{{ studentGroup.sum_not_payed.toLocaleString(intlLocale) }} ₽
                          </span>
                        </div>
                      </div>
                      <!-- Add lesson button for this student -->
                      <IconButton
                        variant="primary"
                        class="shrink-0"
                        :lift="false"
                        :title="t('ui.lessons.add_for_student')"
                        @click.stop="requestAddLessonForStudent(studentGroup)"
                      >
                        <Plus class="h-4 w-4" />
                      </IconButton>
                    </button>

                    <!-- Lessons list (visible when student expanded) -->
                    <Transition name="collapse">
                      <div v-if="studentVisibility[`${yearData.year}-${monthNum}-${studentGroup.student.id}`]" key="student" class="ml-11 mt-2 space-y-2">
                        <Card
                          v-for="lesson in [...studentGroup.lessons].sort((a: Lesson, b: Lesson) => new Date(b.date).getTime() - new Date(a.date).getTime() || (b.time ?? '').localeCompare(a.time ?? ''))"
                          :key="lesson.id"
                          :class="cn(
                            'p-4 transition-all duration-200 hover:shadow-sm',
                            lesson.is_future && 'border-amber-300/50 bg-amber-50/30',
                            !lesson.is_payed && !lesson.is_future && 'border-red-200/50 bg-red-50/20',
                          )"
                        >
                          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex-1 min-w-0 w-full">
                              <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-medium text-foreground">
                                  {{ formatDate(lesson.date) }}
                                </p>
                                <span
                                  v-if="lesson.time"
                                  class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                  <Clock class="h-3 w-3" />
                                  {{ lesson.time.substring(0, 5) }}
                                </span>
                                <span
                                  v-if="lesson.duration"
                                  class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                  <Timer class="h-3 w-3" />
                                  {{ lesson.duration }} {{ t('ui.common.minutes') }}
                                </span>
                              </div>
                              <div class="mt-0.5 flex items-center gap-2 flex-wrap">
                                <p class="text-sm text-muted-foreground">
                                  {{ subjectName(lesson.subject) }}
                                  <span v-if="lesson.topic_name">· {{ lesson.topic_name }}</span>
                                  <span v-if="lesson.subtopic_name">/ {{ lesson.subtopic_name }}</span>
                                </p>
                                <TopicStatusBadge
                                  v-if="lessonTopicStatus(lesson)"
                                  :status="lessonTopicStatus(lesson)"
                                />
                              </div>
                              <p v-if="lesson.comment" class="text-sm text-muted-foreground mt-0.5">
                                {{ lesson.comment }}
                              </p>
                              <div class="flex items-center gap-3 mt-1.5">
                                <span
                                  :class="cn(
                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-sm font-bold',
                                    lesson.is_payed
                                      ? 'bg-emerald-100 text-emerald-700'
                                      : !lesson.is_future
                                        ? 'bg-red-50 text-red-600'
                                        : 'bg-foreground/5 text-foreground',
                                  )"
                                >
                                  {{ lesson.price.toLocaleString(intlLocale) }} ₽
                                </span>
                                <span
                                  :class="cn(
                                    'inline-flex items-center gap-1 text-xs font-medium',
                                    lesson.is_payed ? 'text-emerald-600' : 'text-red-500',
                                  )"
                                >
                                  <Coins v-if="lesson.is_payed" class="h-3.5 w-3.5" />
                                  <Coins v-else class="h-3.5 w-3.5 text-destructive" />
                                  <template v-if="lesson.is_payed">
                                    <span class="hidden sm:inline">{{ t('ui.lessons.paid') }} </span>{{ lesson.date_payed ? formatDatePayed(lesson.date_payed) : '' }}
                                  </template>
                                  <span v-else>{{ t('ui.lessons.not_paid') }}</span>
                                </span>
                                <span
                                  v-if="lesson.is_future"
                                  class="inline-flex items-center gap-1 text-xs font-medium text-amber-600"
                                >
                                  <Calendar class="h-3.5 w-3.5" />
                                  {{ t('ui.lessons.plan') }}
                                </span>
                              </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 sm:shrink-0 self-end sm:self-auto">
                              <!-- Pay button -->
                              <button
                                v-if="!lesson.is_payed"
                                @click="togglePayLesson(lesson.id)"
                                class="inline-flex items-center justify-center gap-1.5 rounded-lg h-8 w-8 sm:w-auto sm:px-3 text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600 transition-colors cursor-pointer"
                                :title="t('ui.lessons.pay_title')"
                              >
                                <Coins class="h-3.5 w-3.5" />
                                <span class="hidden sm:inline">{{ t('ui.lessons.pay') }}</span>
                              </button>
                              <IconButton
                                :title="t('ui.common.edit')"
                                @click="openEditModal(lesson)"
                              >
                                <Pencil class="h-4 w-4" />
                              </IconButton>
                              <IconButton
                                variant="destructive"
                                :title="t('ui.common.delete')"
                                @click="deleteLesson(lesson.id)"
                              >
                                <Trash2 class="h-4 w-4" />
                              </IconButton>
                            </div>
                          </div>
                        </Card>
                      </div>
                    </Transition>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Empty state -->
    <Card v-else class="p-12 text-center">
      <BookOpen class="mx-auto h-12 w-12 text-muted-foreground/30" />
      <p class="mt-4 text-muted-foreground">{{ t('ui.lessons.empty') }}</p>
      <Button @click="requestAddLesson" variant="outline" class="mt-4">
        <Plus class="h-4 w-4" />
        {{ t('ui.lessons.add_first') }}
      </Button>
    </Card>

    <!-- Lesson Form Popup -->
    <LessonFormPopup
      :show="showLessonPopup"
      :mode="lessonPopupMode"
      :students="page.props.students.map(s => ({ id: s.id, name: s.name, current_class: s.current_class }))"
      :subjects="page.props.lessonsSubjects"
      :subjectNames="page.props.subjectNames"
      :topicTree="page.props.topicTree"
      :topicStatuses="page.props.topicStatuses"
      :defaultPrice="page.props.defaultPrice"
      :defaultDuration="page.props.defaultDuration"
      :canAddTopic="page.props.tariff?.can.topics ?? true"
      :initialForm="lessonPopupInitial"
      @close="closeLessonPopup"
      @submit="handleLessonSubmit"
      @delete="handleLessonDelete"
    />

    <!-- Confirm Dialog -->
    <ConfirmDialog
      :show="showConfirm"
      :title="confirmMessage"
      :variant="confirmVariant"
      :confirmText="confirmVariant === 'danger' ? t('ui.common.delete') : t('ui.common.confirm')"
      @confirm="onConfirm"
      @cancel="onCancel"
    />
  </div>
</template>
