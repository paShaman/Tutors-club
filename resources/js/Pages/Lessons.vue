<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { ref, computed } from 'vue'
import { cn } from '@/lib/utils'
import {
  BookOpen,
  Plus,
  Pencil,
  Trash2,
  CheckCircle2,
  XCircle,
  Clock,
  ChevronDown,
  ChevronRight,
  GraduationCap,
  Calendar,
  Timer,
  Star,
} from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

interface Lesson {
  id: number
  student_id: number
  subject: string
  theme: string | null
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
  type: string | null
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
}

const page = usePage<{
  sortedLessons: Record<number, YearGroup>
  students: StudentData[]
  lessonsSubjects: string[]
  defaultPrice: number
  defaultDuration: number
  defaultDate: string
  flash: { success: string | null; error: string | null }
}>()

const monthNames = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь']

const subjectLabels: Record<string, string> = {
  lesson_subject_maths: 'Математика',
  lesson_subject_informatics: 'Информатика',
  lesson_subject_english: 'Английский',
}

function subjectName(key: string): string {
  return subjectLabels[key] ?? key
}

const years = computed(() => {
  return Object.entries(page.props.sortedLessons ?? {})
    .map(([year, data]) => ({ year: Number(year), ...data }))
    .sort((a, b) => b.year - a.year)
})

const yearVisibility = ref<Record<number, boolean>>({})
const monthVisibility = ref<Record<string, boolean>>({})
const studentVisibility = ref<Record<string, boolean>>({})

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

// Modal state
const showModal = ref(false)
const modalMode = ref<'add' | 'edit'>('add')
const editingLesson = ref<Lesson | null>(null)

const form = ref({
  lesson_id: null as number | null,
  lesson_student_id: '' as string,
  lesson_subject: '',
  lesson_theme: '',
  lesson_price: page.props.defaultPrice ?? 2000,
  lesson_duration: page.props.defaultDuration ?? 60,
  lesson_date: page.props.defaultDate ?? '',
  lesson_time: '',
  lesson_date_payed: '',
  lesson_is_payed: false,
  lesson_is_future: false,
})

function openAddModal() {
  modalMode.value = 'add'
  form.value = {
    lesson_id: null,
    lesson_student_id: '',
    lesson_subject: '',
    lesson_theme: '',
    lesson_price: page.props.defaultPrice ?? 2000,
    lesson_duration: page.props.defaultDuration ?? 60,
    lesson_date: page.props.defaultDate ?? '',
    lesson_time: '',
    lesson_date_payed: '',
    lesson_is_payed: false,
    lesson_is_future: false,
  }
  editingLesson.value = null
  showModal.value = true
}

function openAddModalForStudent(studentGroup: StudentGroup) {
  modalMode.value = 'add'

  // Find the most recent lesson for this student to pre-fill data
  const sortedLessons = [...studentGroup.lessons].sort(
    (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime() || (b.time ?? '').localeCompare(a.time ?? '')
  )
  const lastLesson = sortedLessons[0] ?? null

  form.value = {
    lesson_id: null,
    lesson_student_id: String(studentGroup.student.id),
    lesson_subject: lastLesson?.subject ?? '',
    lesson_theme: lastLesson?.theme ?? '',
    lesson_price: lastLesson?.price ?? page.props.defaultPrice ?? 2000,
    lesson_duration: lastLesson?.duration ?? page.props.defaultDuration ?? 60,
    lesson_date: page.props.defaultDate ?? '',
    lesson_time: lastLesson?.time ?? '',
    lesson_date_payed: '',
    lesson_is_payed: false,
    lesson_is_future: false,
  }
  editingLesson.value = null
  showModal.value = true
}

function openEditModal(lesson: Lesson) {
  modalMode.value = 'edit'
  form.value = {
    lesson_id: lesson.id,
    lesson_student_id: String(lesson.student_id),
    lesson_subject: lesson.subject,
    lesson_theme: lesson.theme ?? '',
    lesson_price: lesson.price,
    lesson_duration: lesson.duration,
    lesson_date: lesson.date,
    lesson_time: lesson.time ?? '',
    lesson_date_payed: lesson.date_payed ?? '',
    lesson_is_payed: !!lesson.is_payed,
    lesson_is_future: !!lesson.is_future,
  }
  editingLesson.value = lesson
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingLesson.value = null
}

async function submitLesson() {
  try {
    const response = await fetch('/lessons/edit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(form.value),
    })
    const data = await response.json()

    if (data.success) {
      closeModal()
      window.location.reload()
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}

async function deleteLesson(lessonId: number) {
  if (!confirm('Удалить урок?')) return

  try {
    const response = await fetch('/lessons/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ lesson_id: lessonId }),
    })
    const data = await response.json()

    if (data.success) {
      window.location.reload()
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}

async function togglePayLesson(lessonId: number) {
  try {
    const response = await fetch('/lessons/pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ lesson_id: lessonId }),
    })
    const data = await response.json()

    if (data.success) {
      window.location.reload()
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
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
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
}

function formatDatePayed(dateStr: string | null): string {
  if (!dateStr) return 'Не оплачен'
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
  <Head title="Уроки" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500/10">
          <BookOpen class="h-6 w-6 text-violet-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            Уроки
          </h1>
          <p class="text-sm text-muted-foreground mt-0.5">
            История проведённых занятий
          </p>
        </div>
      </div>

      <Button @click="openAddModal">
        <Plus class="h-4 w-4" />
        Добавить урок
      </Button>
    </div>

    <!-- Flash message -->
    <div
      v-if="page.props.flash?.success"
      class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 border border-emerald-200"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.flash?.error"
      class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 border border-red-200"
    >
      {{ page.props.flash.error }}
    </div>

    <!-- Years -->
    <div v-if="years.length" class="space-y-4">
      <div v-for="yearData in years" :key="yearData.year">
        <!-- Year header -->
        <button
          @click="toggleYear(yearData.year)"
          class="w-full flex items-start lg:items-center gap-3 rounded-2xl bg-gradient-to-r from-primary/5 to-purple-500/5 border border-primary/10 px-5 py-4 hover:from-primary/10 hover:to-purple-500/10 transition-colors cursor-pointer"
        >
          <component
            :is="yearVisibility[yearData.year] ? ChevronDown : ChevronRight"
            class="h-5 w-5 text-primary shrink-0 mt-0.5 lg:mt-0"
          />
          <div class="flex-1 min-w-0 text-left">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">
              <div class="text-left">
                <h2 class="text-xl font-bold text-foreground">{{ yearData.year }}</h2>
                <p class="text-sm text-muted-foreground mt-0.5">
                  {{ yearData.cnt + yearData.cnt_special }} уроков
                  <span v-if="yearData.cnt_special" class="text-amber-600"> (особых: {{ yearData.cnt_special }})</span>
                </p>
              </div>
              <div class="flex items-center gap-2 flex-wrap shrink-0">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                  {{ (yearData.sum + yearData.sum_special).toLocaleString('ru-RU') }} ₽
                </span>
                <span v-if="yearData.sum_special" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-sm font-semibold text-amber-800">
                  <Star class="h-3.5 w-3.5 fill-amber-500 text-amber-600" />
                  {{ yearData.sum_special.toLocaleString('ru-RU') }} ₽
                </span>
                <span v-if="yearData.sum_not_payed" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-sm font-semibold text-red-600">
                  Долг: {{ yearData.sum_not_payed.toLocaleString('ru-RU') }} ₽
                </span>
              </div>
            </div>
          </div>
        </button>

        <!-- Months (visible when year expanded) -->
        <div v-if="yearVisibility[yearData.year]" class="ml-3 mt-2 space-y-3">
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
                class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5 lg:mt-0"
              />
              <div class="flex-1 min-w-0 text-left">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-1.5">
                  <div class="text-left">
                    <h3 class="font-semibold text-foreground">
                      {{ monthNames[Number(monthNum)] }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                      {{ monthData.cnt + monthData.cnt_special }} уроков
                      <span v-if="monthData.cnt_special" class="text-amber-600"> (особых: {{ monthData.cnt_special }})</span>
                    </p>
                  </div>
                  <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                      {{ (monthData.sum + monthData.sum_special).toLocaleString('ru-RU') }} ₽
                    </span>
                    <span v-if="monthData.sum_special" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-xs font-semibold text-amber-800">
                      <Star class="h-3 w-3 fill-amber-500 text-amber-600" />
                      {{ monthData.sum_special.toLocaleString('ru-RU') }} ₽
                    </span>
                    <span v-if="monthData.sum_not_payed" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-xs font-semibold text-red-600">
                      Долг: {{ monthData.sum_not_payed.toLocaleString('ru-RU') }} ₽
                    </span>
                  </div>
                </div>
              </div>
            </button>

            <!-- Students (visible when month expanded) -->
            <div v-if="monthVisibility[`${yearData.year}-${monthNum}`]" class="mt-2 space-y-3">
              <div v-for="studentGroup in sortedStudents(monthData.students)" :key="studentGroup.student.id">
                <!-- Student header (collapsible) -->
                <button
                  @click="toggleStudent(yearData.year, Number(monthNum), studentGroup.student.id)"
                  class="ml-3 w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-accent transition-colors cursor-pointer text-left"
                >
                  <component
                    :is="studentVisibility[`${yearData.year}-${monthNum}-${studentGroup.student.id}`] ? ChevronDown : ChevronRight"
                    class="h-4 w-4 text-muted-foreground shrink-0"
                  />
                  <div
                    :class="cn(
                      'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold text-white shadow shrink-0',
                      studentGroup.student.type
                        ? 'bg-gradient-to-br from-amber-500 to-orange-500'
                        : 'bg-gradient-to-br from-blue-500 to-indigo-500',
                    )"
                  >
                    {{ studentGroup.student.name.charAt(0) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground truncate">
                      {{ studentGroup.student.name }}
                      <span v-if="studentGroup.student.class" class="text-muted-foreground font-normal">
                        · класс {{ studentGroup.student.class }}
                      </span>
                    </p>
                  </div>
                  <div class="text-right shrink-0 space-y-1">
                    <p class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                      {{ (studentGroup.sum + studentGroup.sum_special).toLocaleString('ru-RU') }} ₽
                    </p>
                    <p v-if="studentGroup.sum_special" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-xs font-semibold text-amber-700">
                      <Star class="h-3 w-3" />
                      {{ studentGroup.sum_special.toLocaleString('ru-RU') }} ₽
                    </p>
                    <p v-if="studentGroup.sum_not_payed" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-xs font-semibold text-red-600">
                      Долг: {{ studentGroup.sum_not_payed.toLocaleString('ru-RU') }} ₽
                    </p>
                  </div>
                  <!-- Add lesson button for this student -->
                  <button
                    @click.stop="openAddModalForStudent(studentGroup)"
                    class="inline-flex items-center justify-center rounded-lg h-8 w-8 text-muted-foreground hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer shrink-0"
                    title="Добавить урок ученику"
                  >
                    <Plus class="h-4 w-4" />
                  </button>
                </button>

                <!-- Lessons list (visible when student expanded) -->
                <div v-if="studentVisibility[`${yearData.year}-${monthNum}-${studentGroup.student.id}`]" class="ml-11 space-y-2">
                  <Card
                    v-for="lesson in [...studentGroup.lessons].sort((a: Lesson, b: Lesson) => new Date(b.date).getTime() - new Date(a.date).getTime() || (b.time ?? '').localeCompare(a.time ?? ''))"
                    :key="lesson.id"
                    :class="cn(
                      'p-4 transition-all duration-200 hover:shadow-sm',
                      lesson.is_future && 'border-amber-300/50 bg-amber-50/30',
                      !lesson.is_payed && !lesson.is_future && 'border-red-200/50 bg-red-50/20',
                    )"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div class="flex-1 min-w-0">
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
                            {{ lesson.duration }} мин
                          </span>
                        </div>
                        <p class="text-sm text-muted-foreground mt-0.5">
                          {{ subjectName(lesson.subject) }}
                          <span v-if="lesson.theme">· {{ lesson.theme }}</span>
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
                            {{ lesson.price.toLocaleString('ru-RU') }} ₽
                          </span>
                          <span
                            :class="cn(
                              'inline-flex items-center gap-1 text-xs font-medium',
                              lesson.is_payed ? 'text-emerald-600' : 'text-red-500',
                            )"
                          >
                            <CheckCircle2 v-if="lesson.is_payed" class="h-3.5 w-3.5" />
                            <XCircle v-else class="h-3.5 w-3.5" />
                            {{ lesson.is_payed ? (lesson.date_payed ? `Оплачен ${formatDatePayed(lesson.date_payed)}` : 'Оплачен') : 'Не оплачен' }}
                          </span>
                          <span
                            v-if="lesson.is_future"
                            class="inline-flex items-center gap-1 text-xs font-medium text-amber-600"
                          >
                            <Calendar class="h-3.5 w-3.5" />
                            План
                          </span>
                        </div>
                      </div>

                      <!-- Actions -->
                      <div class="flex items-center gap-1 shrink-0">
                        <!-- Pay button -->
                        <button
                          v-if="!lesson.is_payed"
                          @click="togglePayLesson(lesson.id)"
                          class="inline-flex items-center gap-1.5 rounded-lg h-8 px-3 text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600 transition-colors cursor-pointer"
                          title="Оплатить урок"
                        >
                          <CheckCircle2 class="h-3.5 w-3.5" />
                          Оплатить
                        </button>
                        <!-- Already paid indicator -->
                        <button
                          v-if="lesson.is_payed"
                          class="inline-flex items-center gap-1.5 rounded-lg h-8 px-3 text-xs font-semibold bg-emerald-100 text-emerald-700 cursor-default"
                        >
                          <CheckCircle2 class="h-3.5 w-3.5" />
                          Оплачен
                        </button>
                        <button
                          @click="openEditModal(lesson)"
                          class="inline-flex items-center justify-center rounded-lg h-8 w-8 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                          title="Редактировать"
                        >
                          <Pencil class="h-4 w-4" />
                        </button>
                        <button
                          @click="deleteLesson(lesson.id)"
                          class="inline-flex items-center justify-center rounded-lg h-8 w-8 text-muted-foreground hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
                          title="Удалить"
                        >
                          <Trash2 class="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </Card>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <Card v-else class="p-12 text-center">
      <BookOpen class="mx-auto h-12 w-12 text-muted-foreground/30" />
      <p class="mt-4 text-muted-foreground">Пока нет уроков</p>
      <Button @click="openAddModal" variant="outline" class="mt-4">
        <Plus class="h-4 w-4" />
        Добавить первый урок
      </Button>
    </Card>

    <!-- Add/Edit Modal Overlay -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
      >
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />
        <Card class="relative z-10 w-full max-w-lg p-6 shadow-xl">
          <h2 class="text-lg font-semibold text-foreground mb-5">
            {{ modalMode === 'edit' ? 'Редактировать урок' : 'Новый урок' }}
          </h2>

          <form @submit.prevent="submitLesson" class="space-y-4">
            <!-- Student -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Ученик *</label>
              <select
                v-model="form.lesson_student_id"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="" disabled>Выберите ученика</option>
                <option v-for="student in page.props.students" :key="student.id" :value="student.id">
                  {{ student.name }}{{ student.class ? ` (класс ${student.class})` : '' }}
                </option>
              </select>
            </div>

            <!-- Subject -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Предмет *</label>
              <select
                v-model="form.lesson_subject"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="" disabled>Выберите предмет</option>
                <option v-for="subj in page.props.lessonsSubjects" :key="subj" :value="subj">
                  {{ subjectName(subj) }}
                </option>
              </select>
            </div>

            <!-- Theme -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Тема</label>
              <input
                v-model="form.lesson_theme"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                placeholder="Тема урока"
              />
            </div>

            <!-- Date & Time row -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Дата *</label>
                <input
                  v-model="form.lesson_date"
                  type="date"
                  required
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Время</label>
                <input
                  v-model="form.lesson_time"
                  type="time"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
            </div>

            <!-- Price & Duration row -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Цена (₽)</label>
                <input
                  v-model.number="form.lesson_price"
                  type="number"
                  min="0"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">Длительность (мин)</label>
                <input
                  v-model.number="form.lesson_duration"
                  type="number"
                  min="0"
                  step="5"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
            </div>

            <!-- Toggles -->
            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.lesson_is_payed"
                  type="checkbox"
                  class="rounded border-border text-primary focus:ring-primary/30"
                />
                <span class="text-sm text-foreground">Оплачен</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.lesson_is_future"
                  type="checkbox"
                  class="rounded border-border text-primary focus:ring-primary/30"
                />
                <span class="text-sm text-foreground">План</span>
              </label>
            </div>

            <!-- Date payed (when is_payed checked) -->
            <div v-if="form.lesson_is_payed">
              <label class="block text-sm font-medium text-foreground mb-1.5">Дата оплаты</label>
              <input
                v-model="form.lesson_date_payed"
                type="date"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
              <Button type="submit" class="flex-1">
                {{ modalMode === 'edit' ? 'Сохранить' : 'Добавить' }}
              </Button>
              <Button type="button" variant="outline" class="flex-1" @click="closeModal">
                Отмена
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </Teleport>
  </div>
</template>