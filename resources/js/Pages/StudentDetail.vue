<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import StudentAvatar from '@/components/ui/StudentAvatar.vue'
import TopicStatusBadge from '@/components/ui/TopicStatusBadge.vue'
import ReviewFormPopup from '@/components/popups/ReviewFormPopup.vue'
import type { ReviewFormData } from '@/components/popups/ReviewFormPopup.vue'
import { useToast } from '@/lib/toast'
import { computed, onMounted, ref } from 'vue'
import { cn } from '@/lib/utils'
import { useI18n } from '@/lib/i18n'
import {
  ArrowLeft,
  BookOpen,
  Calendar,
  CalendarClock,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Clock,
  Coins,
  Eye,
  EyeOff,
  GraduationCap,
  ListChecks,
  Plus,
  RotateCcw,
  Star,
  StickyNote,
  Timer,
  TrendingUp,
  UserPlus,
  Wallet,
} from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

interface LessonItem {
  id: number
  subject: string
  topic_id: number | null
  subtopic_id: number | null
  topic_name: string | null
  subtopic_name: string | null
  comment: string | null
  price: number
  duration: number
  date: string
  time: string | null
  is_payed: number
  is_future: number
  date_payed: string | null
}

interface MonthGroup {
  sum: number
  debt: number
  cnt: number
  cnt_all: number
  lessons: LessonItem[]
}

interface YearGroup {
  sum: number
  debt: number
  cnt: number
  cnt_all: number
  months: Record<number, MonthGroup>
}

interface StudentDetailData {
  id: number
  name: string
  class: string | null
  current_class: string
  type: string | null
  description: string | null
  gender: string
  color: string | null
  is_deleted: number
  created_at: string | null
}

interface Summary {
  total_lessons: number
  paid_lessons: number
  earned: number
  debt: number
  total_minutes: number
  first_lesson_date: string | null
  last_lesson_date: string | null
  lessons_planned: number
}

interface TopicNode {
  id: number
  name: string
  position: number
  children: TopicNode[]
}

interface TopicState {
  topic_id: number
  status: string
  mastered_at: string | null
  last_reviewed_at: string | null
  reviews_count: number
  last_review_comment: string | null
}

const page = usePage<{
  student: StudentDetailData
  summary: Summary
  sortedLessons: Record<number, YearGroup>
  subjects: string[]
  subjectNames: Record<string, string>
  topicsBySubject: Record<string, TopicNode[]>
  topicStates: TopicState[]
}>()

const toast = useToast()
const { t, tp, intlLocale } = useI18n()

const student = computed(() => page.props.student)
const summary = computed(() => page.props.summary)

const years = computed(() =>
  Object.entries(page.props.sortedLessons ?? {})
    .map(([year, data]) => ({ year: Number(year), ...data }))
    .sort((a, b) => b.year - a.year),
)

const totalHours = computed(() => Math.round((summary.value.total_minutes / 60) * 10) / 10)

const monthNames = computed(() => {
  const formatter = new Intl.DateTimeFormat(intlLocale.value, { month: 'long' })
  const names = ['']
  for (let m = 0; m < 12; m++) {
    const name = formatter.format(new Date(2020, m, 1))
    names.push(name.charAt(0).toUpperCase() + name.slice(1))
  }
  return names
})

const yearVisibility = ref<Record<number, boolean>>({})
const monthVisibility = ref<Record<string, boolean>>({})

onMounted(() => {
  const now = new Date()
  yearVisibility.value[now.getFullYear()] = true
  monthVisibility.value[`${now.getFullYear()}-${now.getMonth() + 1}`] = true
})

function toggleYear(year: number) {
  yearVisibility.value[year] = !yearVisibility.value[year]
}

function toggleMonth(year: number, month: number) {
  const key = `${year}-${month}`
  monthVisibility.value[key] = !monthVisibility.value[key]
}

function subjectName(key: string): string {
  return page.props.subjectNames?.[key] ?? t(key)
}

function formatNumber(value: number): string {
  return value.toLocaleString(intlLocale.value)
}

function formatFullDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString(intlLocale.value, { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatShortDate(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleDateString(intlLocale.value, { day: 'numeric', month: 'long' })
}

function formatDatePayed(dateStr: string | null): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')

  if (hours === '00' && minutes === '00') {
    return `${day}.${month}.${year}`
  }
  return `${day}.${month}.${year} ${hours}:${minutes}`
}

// ─── Planning / topics ──────────────────────────────────────
const activeTab = ref<'stats' | 'topics'>('stats')

const subjects = computed<string[]>(() => page.props.subjects ?? [])

const topicsSubject = ref<string>(page.props.subjects?.[0] ?? '')

const stateMap = computed<Record<number, TopicState>>(() => {
  const map: Record<number, TopicState> = {}
  for (const state of page.props.topicStates ?? []) {
    map[state.topic_id] = state
  }
  return map
})

const currentTree = computed<TopicNode[]>(
  () => page.props.topicsBySubject?.[topicsSubject.value] ?? [],
)

const showNotStarted = ref(false)
const statusFilter = ref<'all' | 'not_started' | 'in_progress' | 'mastered'>('all')

function subjectLabel(key: string): string {
  return page.props.subjectNames?.[key] ?? t(key)
}

function statusOf(topicId: number): string {
  return stateMap.value[topicId]?.status ?? 'not_started'
}

function matchesFilter(topicId: number): boolean {
  const status = statusOf(topicId)

  if (statusFilter.value !== 'all' && status !== statusFilter.value) return false
  if (!showNotStarted.value && status === 'not_started') return false

  return true
}

const visibleTree = computed(() =>
  currentTree.value
    .map((root) => {
      const visibleChildren = (root.children ?? []).filter((child) => matchesFilter(child.id))
      return {
        ...root,
        visibleChildren,
        visible: matchesFilter(root.id) || visibleChildren.length > 0,
      }
    })
    .filter((root) => root.visible),
)

const topicsProgress = computed(() => {
  let total = 0
  let done = 0

  const count = (node: TopicNode): void => {
    total++
    if (statusOf(node.id) === 'mastered') done++
    for (const child of node.children ?? []) count(child)
  }

  for (const root of currentTree.value) count(root)

  return { total, done }
})

function setStatus(topicId: number, status: string): void {
  router.post('/student-topics/status', {
    student_id: student.value.id,
    topic_id: topicId,
    status,
  }, {
    preserveScroll: true,
    onError: () => toast.error(t('error.set_topic_status')),
  })
}

function setMasteredAt(topicId: number, date: string): void {
  if (!date) return

  router.post('/student-topics/status', {
    student_id: student.value.id,
    topic_id: topicId,
    status: 'mastered',
    mastered_at: date,
  }, {
    preserveScroll: true,
    onError: () => toast.error(t('error.set_topic_status')),
  })
}

function onStatusChange(topicId: number, event: Event): void {
  setStatus(topicId, (event.target as HTMLSelectElement).value)
}

function onMasteredAtChange(topicId: number, event: Event): void {
  setMasteredAt(topicId, (event.target as HTMLInputElement).value)
}

function showAll(): void {
  showNotStarted.value = true
  statusFilter.value = 'all'
}

// ─── Review popup ───────────────────────────────────────────
const showReviewForm = ref(false)
const reviewTopic = ref<{ id: number; name: string } | null>(null)

function openReview(topic: { id: number; name: string }): void {
  reviewTopic.value = topic
  showReviewForm.value = true
}

function submitReview(form: ReviewFormData): void {
  if (!reviewTopic.value) return

  router.post('/student-topics/review', {
    student_id: student.value.id,
    topic_id: reviewTopic.value.id,
    reviewed_on: form.reviewed_on,
    comment: form.comment,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showReviewForm.value = false
    },
    onError: () => toast.error(t('error.review_topic')),
  })
}

function formatTopicDate(dateStr: string | null): string {
  return formatFullDate(dateStr)
}
</script>

<template>
  <Head :title="student.name" />

  <div class="space-y-6 animate-fade-up">
    <!-- Back link -->
    <Link
      href="/students"
      class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
    >
      <ArrowLeft class="h-4 w-4" />
      {{ t('ui.student.back') }}
    </Link>

    <!-- Profile -->
    <Card class="overflow-hidden">
      <div class="bg-gradient-to-br from-primary/5 via-primary/10 to-purple-500/5 p-6">
        <div class="flex items-start gap-5 flex-wrap">
          <StudentAvatar
            :name="student.name"
            :gender="student.gender"
            :color="student.color"
            class="h-20 w-20"
          />

          <div class="flex-1 min-w-[220px]">
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ student.name }}</h1>
              <span
                v-if="student.type"
                class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
              >
                <Star class="h-3.5 w-3.5 fill-amber-500 text-amber-600" />
                {{ student.type }}
              </span>
              <span
                v-if="student.is_deleted"
                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"
              >
                {{ t('ui.students.deleted') }}
              </span>
            </div>

            <div class="mt-1.5 flex items-center gap-2 text-sm text-muted-foreground">
              <GraduationCap class="h-4 w-4" />
              {{ student.current_class || t('ui.students.class_not_set') }}
            </div>

            <div v-if="student.description" class="mt-3 flex items-start gap-2 text-sm text-foreground/80">
              <StickyNote class="h-4 w-4 shrink-0 mt-0.5 text-muted-foreground" />
              <p class="whitespace-pre-line">{{ student.description }}</p>
            </div>
            <p v-else class="mt-3 text-sm italic text-muted-foreground">
              {{ t('ui.student.no_note') }}
            </p>
          </div>

          <Link :href="`/lessons?student_id=${student.id}`" class="shrink-0">
            <Button variant="outline" size="sm">
              <BookOpen class="h-4 w-4" />
              {{ t('ui.student.all_lessons') }}
            </Button>
          </Link>
        </div>
      </div>
    </Card>

    <!-- Tabs -->
    <div class="inline-flex rounded-xl border border-border bg-white/60 p-1">
      <button
        :class="cn(
          'rounded-lg px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
          activeTab === 'stats' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
        )"
        @click="activeTab = 'stats'"
      >
        {{ t('ui.planning.tabs.stats') }}
      </button>
      <button
        :class="cn(
          'rounded-lg px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
          activeTab === 'topics' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
        )"
        @click="activeTab = 'topics'"
      >
        {{ t('ui.planning.tabs.topics') }}
      </button>
    </div>

    <template v-if="activeTab === 'stats'">
    <!-- Summary -->
    <div>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
        {{ t('ui.student.summary_title') }}
      </h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card class="p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                {{ t('ui.student.total_lessons') }}
              </p>
              <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground">
                {{ summary.total_lessons }}
              </p>
              <p v-if="summary.total_minutes" class="mt-1 text-xs text-muted-foreground">
                {{ totalHours }} {{ t('ui.student.hours_short') }}
              </p>
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10">
              <BookOpen class="h-5 w-5 text-primary" />
            </div>
          </div>
        </Card>

        <Card class="p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                {{ t('ui.student.paid_lessons') }}
              </p>
              <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground">
                {{ summary.paid_lessons }}
              </p>
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
              <CheckCircle2 class="h-5 w-5 text-emerald-600" />
            </div>
          </div>
        </Card>

        <Card class="p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                {{ t('ui.student.earned') }}
              </p>
              <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground whitespace-nowrap">
                {{ formatNumber(summary.earned) }} ₽
              </p>
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-500/10">
              <Wallet class="h-5 w-5 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card class="p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                {{ t('ui.student.debt') }}
              </p>
              <p
                :class="cn(
                  'mt-2 text-2xl xl:text-3xl font-bold whitespace-nowrap',
                  summary.debt > 0 ? 'text-red-600' : 'text-foreground',
                )"
              >
                {{ formatNumber(summary.debt) }} ₽
              </p>
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-500/10">
              <TrendingUp class="h-5 w-5 text-red-500" />
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Key dates -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <Card class="flex items-center gap-3 p-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/10">
          <Calendar class="h-5 w-5 text-violet-600" />
        </div>
        <div class="min-w-0">
          <p class="text-xs text-muted-foreground">{{ t('ui.student.started') }}</p>
          <p class="text-sm font-semibold text-foreground truncate">
            {{ formatFullDate(summary.first_lesson_date) }}
          </p>
        </div>
      </Card>

      <Card class="flex items-center gap-3 p-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10">
          <CalendarClock class="h-5 w-5 text-blue-600" />
        </div>
        <div class="min-w-0">
          <p class="text-xs text-muted-foreground">{{ t('ui.student.last_lesson') }}</p>
          <p class="text-sm font-semibold text-foreground truncate">
            {{ formatFullDate(summary.last_lesson_date) }}
          </p>
        </div>
      </Card>

      <Card class="flex items-center gap-3 p-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
          <UserPlus class="h-5 w-5 text-emerald-600" />
        </div>
        <div class="min-w-0">
          <p class="text-xs text-muted-foreground">{{ t('ui.student.added') }}</p>
          <p class="text-sm font-semibold text-foreground truncate">
            {{ formatFullDate(student.created_at) }}
          </p>
        </div>
      </Card>

      <Card class="flex items-center gap-3 p-4">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10">
          <CalendarClock class="h-5 w-5 text-amber-600" />
        </div>
        <div class="min-w-0">
          <p class="text-xs text-muted-foreground">{{ t('ui.student.planned') }}</p>
          <p class="text-sm font-semibold text-foreground truncate">
            {{ tp('ui.lessons.count', summary.lessons_planned) }}
          </p>
        </div>
      </Card>
    </div>

    <!-- Lessons history -->
    <div>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
        {{ t('ui.student.lessons_history') }}
      </h2>

      <div v-if="years.length" class="space-y-4">
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
                <div class="flex items-center gap-2.5 flex-wrap">
                  <h3 class="text-xl font-bold text-foreground">{{ yearData.year }}</h3>
                  <p class="text-sm text-muted-foreground">
                    {{ tp('ui.lessons.count', yearData.cnt_all) }}
                  </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap shrink-0">
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-sm font-bold text-emerald-700">
                    {{ formatNumber(yearData.sum) }} ₽
                  </span>
                  <span
                    v-if="yearData.debt"
                    class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-sm font-semibold text-red-600"
                  >
                    {{ t('ui.lessons.debt') }} {{ formatNumber(yearData.debt) }} ₽
                  </span>
                </div>
              </div>
            </div>
          </button>

          <!-- Months -->
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
                      <div class="flex items-center gap-2.5 flex-wrap">
                        <h4 class="font-semibold text-foreground">
                          {{ monthNames[Number(monthNum)] }}
                        </h4>
                        <p class="text-xs text-muted-foreground">
                          {{ tp('ui.lessons.count', monthData.cnt_all) }}
                        </p>
                      </div>
                      <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">
                          {{ formatNumber(monthData.sum) }} ₽
                        </span>
                        <span
                          v-if="monthData.debt"
                          class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600"
                        >
                          {{ t('ui.lessons.debt') }} {{ formatNumber(monthData.debt) }} ₽
                        </span>
                      </div>
                    </div>
                  </div>
                </button>

                <!-- Lessons -->
                <Transition name="collapse">
                  <div v-if="monthVisibility[`${yearData.year}-${monthNum}`]" key="month" class="ml-6 mt-2 space-y-2">
                    <Card
                      v-for="lesson in monthData.lessons"
                      :key="lesson.id"
                      :class="cn(
                        'p-4 transition-all duration-200 hover:shadow-sm',
                        lesson.is_future && 'border-amber-300/50 bg-amber-50/30',
                        !lesson.is_payed && !lesson.is_future && 'border-red-200/50 bg-red-50/20',
                      )"
                    >
                      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-medium text-foreground">
                              {{ formatShortDate(lesson.date) }}
                            </p>
                            <span
                              v-if="lesson.time"
                              class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                            >
                              <Clock class="h-3 w-3" />
                              {{ lesson.time }}
                            </span>
                            <span
                              v-if="lesson.duration"
                              class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                            >
                              <Timer class="h-3 w-3" />
                              {{ lesson.duration }} {{ t('ui.common.minutes') }}
                            </span>
                          </div>
                          <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ subjectName(lesson.subject) }}
                            <span v-if="lesson.topic_name">· {{ lesson.topic_name }}</span>
                            <span v-if="lesson.subtopic_name">/ {{ lesson.subtopic_name }}</span>
                          </p>
                          <p v-if="lesson.comment" class="mt-0.5 text-sm text-muted-foreground">
                            {{ lesson.comment }}
                          </p>
                          <div class="mt-1.5 flex items-center gap-3 flex-wrap">
                            <span
                              :class="cn(
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-bold',
                                lesson.is_payed
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : !lesson.is_future
                                    ? 'bg-red-50 text-red-600'
                                    : 'bg-foreground/5 text-foreground',
                              )"
                            >
                              {{ formatNumber(lesson.price) }} ₽
                            </span>
                            <span
                              :class="cn(
                                'inline-flex items-center gap-1 text-xs font-medium',
                                lesson.is_payed ? 'text-emerald-600' : 'text-red-500',
                              )"
                            >
                              <Coins class="h-3.5 w-3.5" />
                              <template v-if="lesson.is_payed">
                                <span class="hidden sm:inline">{{ t('ui.lessons.paid') }}</span>
                                {{ lesson.date_payed ? formatDatePayed(lesson.date_payed) : '' }}
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
                      </div>
                    </Card>
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
        <p class="mt-4 text-muted-foreground">{{ t('ui.student.empty_lessons') }}</p>
        <Link :href="`/lessons?student_id=${student.id}`" class="mt-4 inline-block">
          <Button variant="outline">
            <Plus class="h-4 w-4" />
            {{ t('ui.student.add_lesson') }}
          </Button>
        </Link>
      </Card>
    </div>
    </template>

    <!-- Topics -->
    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="subject in subjects"
            :key="subject"
            :class="cn(
              'rounded-xl border px-4 py-2 text-sm font-medium transition-colors cursor-pointer',
              subject === topicsSubject
                ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                : 'border-border bg-white/60 text-muted-foreground hover:bg-accent hover:text-foreground',
            )"
            @click="topicsSubject = subject"
          >
            {{ subjectLabel(subject) }}
          </button>
        </div>
        <p v-if="topicsProgress.total" class="text-sm text-muted-foreground">
          {{ t('ui.planning.progress', { done: topicsProgress.done, total: topicsProgress.total }) }}
        </p>
      </div>

      <Card class="flex flex-wrap items-center gap-3 p-3">
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-muted-foreground">
            {{ t('ui.planning.filters.status') }}
          </label>
          <select
            v-model="statusFilter"
            class="rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
          >
            <option value="all">{{ t('ui.planning.filters.all') }}</option>
            <option value="not_started">{{ t('ui.planning.status.not_started') }}</option>
            <option value="in_progress">{{ t('ui.planning.status.in_progress') }}</option>
            <option value="mastered">{{ t('ui.planning.status.mastered') }}</option>
          </select>
        </div>
        <button
          class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-muted-foreground transition-colors hover:text-foreground cursor-pointer"
          @click="showNotStarted = !showNotStarted"
        >
          <component :is="showNotStarted ? EyeOff : Eye" class="h-4 w-4" />
          {{ showNotStarted ? t('ui.planning.filters.hide_not_started') : t('ui.planning.filters.show_not_started') }}
        </button>
      </Card>

      <Card v-if="!currentTree.length" class="p-12 text-center">
        <ListChecks class="mx-auto h-12 w-12 text-muted-foreground/30" />
        <p class="mt-4 font-medium text-foreground">{{ t('ui.planning.student_empty') }}</p>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.planning.student_empty_hint') }}</p>
      </Card>

      <Card v-else-if="!visibleTree.length" class="p-12 text-center">
        <Eye class="mx-auto h-12 w-12 text-muted-foreground/30" />
        <p class="mt-4 font-medium text-foreground">{{ t('ui.planning.filtered_empty') }}</p>
        <Button class="mt-4" variant="outline" @click="showAll">
          <Eye class="h-4 w-4" />
          {{ t('ui.planning.show_all') }}
        </Button>
      </Card>

      <div v-else class="space-y-3">
        <Card v-for="root in visibleTree" :key="root.id" class="p-4">
          <div class="flex flex-wrap items-center gap-3">
            <div class="min-w-0 flex-1">
              <p class="truncate font-semibold text-foreground">{{ root.name }}</p>
              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                <TopicStatusBadge :status="statusOf(root.id)" />
                <span v-if="stateMap[root.id]?.mastered_at">
                  {{ t('ui.planning.mastered_at') }}: {{ formatTopicDate(stateMap[root.id]?.mastered_at) }}
                </span>
                <span>
                  {{ stateMap[root.id]?.last_reviewed_at
                    ? t('ui.planning.last_reviewed') + ': ' + formatTopicDate(stateMap[root.id]?.last_reviewed_at)
                    : t('ui.planning.never_reviewed') }}
                </span>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <select
                :value="statusOf(root.id)"
                class="rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                @change="onStatusChange(root.id, $event)"
              >
                <option value="not_started">{{ t('ui.planning.status.not_started') }}</option>
                <option value="in_progress">{{ t('ui.planning.status.in_progress') }}</option>
                <option value="mastered">{{ t('ui.planning.status.mastered') }}</option>
              </select>
              <input
                v-if="statusOf(root.id) === 'mastered'"
                type="date"
                :value="stateMap[root.id]?.mastered_at ?? ''"
                class="rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                @change="onMasteredAtChange(root.id, $event)"
              />
              <Button variant="outline" size="sm" @click="openReview({ id: root.id, name: root.name })">
                <RotateCcw class="h-4 w-4" />
                {{ t('ui.planning.review_short') }}
              </Button>
            </div>
          </div>

          <div v-if="root.visibleChildren.length" class="mt-3 space-y-1 border-t border-border/60 pt-3">
            <div
              v-for="child in root.visibleChildren"
              :key="child.id"
              class="flex flex-wrap items-center gap-3 rounded-xl px-2 py-1.5 hover:bg-accent/40"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-foreground">{{ child.name }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                  <TopicStatusBadge :status="statusOf(child.id)" />
                  <span v-if="stateMap[child.id]?.mastered_at">
                    {{ t('ui.planning.mastered_at') }}: {{ formatTopicDate(stateMap[child.id]?.mastered_at) }}
                  </span>
                  <span>
                    {{ stateMap[child.id]?.last_reviewed_at
                      ? t('ui.planning.last_reviewed') + ': ' + formatTopicDate(stateMap[child.id]?.last_reviewed_at)
                      : t('ui.planning.never_reviewed') }}
                  </span>
                  <span v-if="stateMap[child.id]?.reviews_count">
                    {{ tp('ui.planning.reviews_count', stateMap[child.id]?.reviews_count ?? 0) }}
                  </span>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <select
                  :value="statusOf(child.id)"
                  class="rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                  @change="onStatusChange(child.id, $event)"
                >
                  <option value="not_started">{{ t('ui.planning.status.not_started') }}</option>
                  <option value="in_progress">{{ t('ui.planning.status.in_progress') }}</option>
                  <option value="mastered">{{ t('ui.planning.status.mastered') }}</option>
                </select>
                <input
                  v-if="statusOf(child.id) === 'mastered'"
                  type="date"
                  :value="stateMap[child.id]?.mastered_at ?? ''"
                  class="rounded-xl border border-border bg-white/50 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                  @change="onMasteredAtChange(child.id, $event)"
                />
                <Button variant="outline" size="sm" @click="openReview({ id: child.id, name: child.name })">
                  <RotateCcw class="h-4 w-4" />
                  {{ t('ui.planning.review_short') }}
                </Button>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </template>

    <ReviewFormPopup
      :show="showReviewForm"
      :topic-name="reviewTopic?.name ?? ''"
      @close="showReviewForm = false"
      @submit="submitReview"
    />
  </div>
</template>
