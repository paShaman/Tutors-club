<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Tabs from '@/components/ui/Tabs.vue'
import type { TabItem } from '@/components/ui/Tabs.vue'
import FullCalendar from '@fullcalendar/vue3'
import classicTheme from '@fullcalendar/vue3/themes/classic'
import dayGridPlugin from '@fullcalendar/vue3/daygrid'
import timeGridPlugin from '@fullcalendar/vue3/timegrid'
import interactionPlugin from '@fullcalendar/vue3/interaction'
import ruLocale from '@fullcalendar/vue3/locales/ru'
import enLocale from '@fullcalendar/vue3/locales/en-gb'
import type { CalendarOptions, EventClickInfo } from '@fullcalendar/vue3'
import LessonFormPopup from '@/components/popups/LessonFormPopup.vue'
import type { LessonFormData, TopicNode } from '@/components/popups/LessonFormPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'
import type { TariffInfo } from '@/types'
// Стили темы v7 подключаются вместе с переопределениями (resources/css/fullcalendar.css)
import '../../css/fullcalendar.css'

// Соответствие кода языка и локали FullCalendar (список расширяется вместе с config/locales.php)
const FC_LOCALES: Record<string, any> = {
  ru: ruLocale,
  en: enLocale,
}

defineOptions({ layout: AppLayout })

const page = usePage<{
  students: Array<{
    id: number
    name: string
    current_class: string
    type: string | null
  }>
  lessonsSubjects: string[]
  subjectNames: Record<string, string>
  topicTree: Record<string, TopicNode[]>
  topicStatuses: Record<number, Record<number, string>>
  defaultPrice: number
  defaultDuration: number
  defaultDate: string
  tariff: TariffInfo | null
}>()

const toast = useToast()
const { t, locale } = useI18n()

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)

// FullCalendar сам ходит за событиями (events ниже), мимо Inertia, поэтому о загрузке
// узнаём только из его колбэка loading.
const isEventsLoading = ref(false)

const fullCalendarLocale = computed(() => FC_LOCALES[locale.value] ?? ruLocale)

// Заголовок и активный период синхронизируем с самим календарём через datesSet.
const calendarTitle = ref('')
const currentView = ref('dayGridMonth')

const periodItems = computed<TabItem[]>(() => [
  { value: 'dayGridMonth', label: t('ui.calendar.month') },
  { value: 'timeGridWeek', label: t('ui.calendar.week') },
  { value: 'timeGridDay', label: t('ui.calendar.day') },
])

const calendarOptions = computed<CalendarOptions>(() => ({
  plugins: [classicTheme, dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locales: [fullCalendarLocale.value],
  locale: fullCalendarLocale.value,
  events: '/calendar/events',
  loading: (isLoading: boolean) => {
    isEventsLoading.value = isLoading
  },
  eventClick: handleEventClick,
  datesSet: (arg) => {
    calendarTitle.value = arg.view.title
    currentView.value = arg.view.type
  },
  editable: false,
  selectable: false,
  firstDay: 1,
  height: '100%',
  // В v7 заголовок недели по умолчанию без чисел — возвращаем привычный диапазон дат
  views: {
    timeGridWeek: {
      titleFormat: {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      },
    },
  },
  eventTimeFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  },
  // Классы, за которые цепляются проектные стили (resources/css/fullcalendar.css)
  eventClass: (info) =>
    info.event.extendedProps.is_future
      ? 'cal-event cal-event--future'
      : info.event.extendedProps.is_payed
        ? 'cal-event'
        : 'cal-event cal-event--unpaid',
  eventTimeClass: 'cal-event-time',
  eventTitleClass: 'cal-event-title',
  moreLinkInnerClass: 'cal-more-link',
  dayHeaderInnerClass: 'cal-day-header',
  dayCellTopInnerClass: 'cal-day-number',
  slotHeaderInnerClass: 'cal-slot-header',
}))

function goPrev(): void {
  calendarRef.value?.getApi()?.prev()
}

function goNext(): void {
  calendarRef.value?.getApi()?.next()
}

function goToday(): void {
  calendarRef.value?.getApi()?.today()
}

function changeView(view: string): void {
  calendarRef.value?.getApi()?.changeView(view)
}

// Popup state
const showLessonPopup = ref(false)
const lessonPopupInitial = ref<LessonFormData | null>(null)
const lessonSubmitting = ref(false)

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

function handleEventClick(arg: EventClickInfo) {
  const props = arg.event.extendedProps

  lessonPopupInitial.value = {
    lesson_id: Number(arg.event.id),
    lesson_student_id: String(props.student_id),
    lesson_subject: props.subject,
    lesson_topic_id: props.topic_id ?? null,
    lesson_subtopic_id: props.subtopic_id ?? null,
    lesson_topic_status: 'in_progress',
    lesson_comment: props.comment ?? '',
    lesson_price: props.price,
    lesson_duration: props.duration,
    lesson_date: props.date,
    lesson_time: props.time ?? '',
    lesson_date_payed: props.date_payed ?? '',
    lesson_is_payed: !!props.is_payed,
    lesson_is_future: !!props.is_future,
  }

  showLessonPopup.value = true
}

function closeLessonPopup() {
  showLessonPopup.value = false
}

function refetchEvents() {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    calendarApi.refetchEvents()
  }
}

function handleLessonSubmit(form: LessonFormData) {
  if (lessonSubmitting.value) return

  lessonSubmitting.value = true
  router.post('/lessons/edit', form, {
    preserveScroll: true,
    onSuccess: () => {
      closeLessonPopup()
      refetchEvents()
    },
    onError: (errors) => toast.error(Object.values(errors).join('\n')),
    onFinish: () => { lessonSubmitting.value = false },
  })
}

function handleLessonDelete() {
  const lessonId = lessonPopupInitial.value?.lesson_id
  if (!lessonId) return
  openConfirm(t('ui.lessons.delete_confirm'), 'danger', () => {
    router.post('/lessons/delete', { lesson_id: lessonId }, {
      preserveScroll: true,
      onSuccess: () => {
        closeLessonPopup()
        refetchEvents()
      },
      onError: (errors) => toast.error(Object.values(errors).join('\n')),
    })
  })
}
</script>

<template>
  <Head :title="t('ui.calendar.title')" />

  <div class="flex h-[calc(100vh-7rem)] flex-col gap-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex shrink-0 items-center justify-between flex-wrap gap-4">
      <h1 class="text-2xl font-bold tracking-tight text-foreground">
        {{ t('ui.calendar.title') }}
      </h1>
    </div>

    <!-- Calendar -->
    <Card class="fc-theme-custom flex min-h-0 flex-1 flex-col overflow-hidden p-4">
      <div class="mb-3 flex shrink-0 flex-col items-start gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="order-1 flex items-center gap-2 lg:order-none">
          <Button
            variant="outline"
            size="icon"
            :aria-label="t('ui.calendar.prev')"
            :title="t('ui.calendar.prev')"
            @click="goPrev"
          >
            <ChevronLeft />
          </Button>
          <Button
            variant="outline"
            size="icon"
            :aria-label="t('ui.calendar.next')"
            :title="t('ui.calendar.next')"
            @click="goNext"
          >
            <ChevronRight />
          </Button>
          <Button variant="outline" @click="goToday">
            {{ t('ui.calendar.today') }}
          </Button>
        </div>

        <div class="order-2 flex w-full items-center justify-center gap-2 lg:order-none lg:w-auto lg:flex-1">
          <h2 class="text-lg font-semibold tracking-tight text-foreground">
            {{ calendarTitle }}
          </h2>
          <Loader2
            v-if="isEventsLoading"
            class="h-4 w-4 shrink-0 animate-spin text-muted-foreground"
            role="img"
            :aria-label="t('ui.common.loading')"
          />
        </div>

        <Tabs
          class="order-3 lg:order-none"
          :model-value="currentView"
          :items="periodItems"
          @update:model-value="changeView"
        />
      </div>

      <!-- ВАЖНО: у <FullCalendar> не должно быть динамических классов. Vue перезаписывает
           атрибут class целиком и стирает классы (fc-pp, fc-vg, …), которые библиотека
           вешает на свой корень императивно, — после этого flex-раскладка схлопывается.
           Поэтому приглушение загрузки живёт на обёртке, а у календаря классы статичные. -->
      <div
        :class="cn('flex min-h-0 flex-1 flex-col transition-opacity duration-200', isEventsLoading && 'opacity-60 delay-200')"
        :aria-busy="isEventsLoading"
      >
        <FullCalendar
          ref="calendarRef"
          class="min-h-0 min-w-0 flex-1"
          :options="calendarOptions"
        />
      </div>
    </Card>

    <!-- Lesson Form Popup -->
    <LessonFormPopup
      :show="showLessonPopup"
      mode="edit"
      :students="page.props.students.map(s => ({ id: s.id, name: s.name, current_class: s.current_class }))"
      :subjects="page.props.lessonsSubjects"
      :subjectNames="page.props.subjectNames"
      :topicTree="page.props.topicTree"
      :topicStatuses="page.props.topicStatuses"
      :defaultPrice="page.props.defaultPrice"
      :defaultDuration="page.props.defaultDuration"
      :canAddTopic="page.props.tariff?.can.topics ?? true"
      :initialForm="lessonPopupInitial"
      :submitting="lessonSubmitting"
      @close="closeLessonPopup"
      @submit="handleLessonSubmit"
      @delete="handleLessonDelete"
    />

    <ConfirmDialog
      :show="showConfirm"
      :title="confirmMessage"
      :variant="confirmVariant"
      @confirm="onConfirm"
      @cancel="onCancel"
    />
  </div>
</template>
