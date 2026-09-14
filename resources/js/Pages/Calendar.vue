<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Tabs from '@/components/ui/Tabs.vue'
import type { TabItem } from '@/components/ui/Tabs.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import enLocale from '@fullcalendar/core/locales/en-gb'
import type { CalendarOptions, EventClickArg } from '@fullcalendar/core'
import LessonFormPopup from '@/components/popups/LessonFormPopup.vue'
import type { LessonFormData, TopicNode } from '@/components/popups/LessonFormPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import type { TariffInfo } from '@/types'

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
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locales: [fullCalendarLocale.value],
  locale: fullCalendarLocale.value,
  headerToolbar: false,
  events: '/calendar/events',
  eventClick: handleEventClick,
  datesSet: (arg) => {
    calendarTitle.value = arg.view.title
    currentView.value = arg.view.type
  },
  editable: false,
  selectable: false,
  firstDay: 1,
  height: '100%',
  eventTimeFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  },
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

function handleEventClick(arg: EventClickArg) {
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
  router.post('/lessons/edit', form, {
    preserveScroll: true,
    onSuccess: () => {
      closeLessonPopup()
      refetchEvents()
    },
    onError: (errors) => toast.error(Object.values(errors).join('\n')),
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

        <h2 class="order-2 w-full text-center text-lg font-semibold tracking-tight text-foreground lg:order-none lg:w-auto lg:flex-1">
          {{ calendarTitle }}
        </h2>

        <Tabs
          class="order-3 lg:order-none"
          :model-value="currentView"
          :items="periodItems"
          @update:model-value="changeView"
        />
      </div>

      <FullCalendar
        ref="calendarRef"
        class="min-h-0 flex-1"
        :options="calendarOptions"
      />
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

<style>
.fc-theme-custom {
  --fc-border-color: hsl(214.3 31.8% 91.4%);
  --fc-today-bg-color: hsl(252 87% 67% / 0.05);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: transparent;
}

.fc-theme-custom .fc-event {
  border: none;
  border-radius: 0.5rem;
  padding: 2px 6px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: filter 0.2s ease, transform 0.2s ease;
}
.fc-theme-custom .fc-event:hover {
  filter: brightness(0.92);
  transform: translateY(-1px);
}

.fc-theme-custom .fc-daygrid-day-number {
  padding: 6px 8px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: hsl(215.4 16.3% 46.9%);
}

/* --- Mobile: compact day cells --- */
@media (max-width: 639px) {
  /* Month view: compact day cells */
  .fc-theme-custom .fc-daygrid-day-number {
    padding: 2px 4px !important;
    font-size: 0.6875rem !important;
  }

  .fc-theme-custom .fc-daygrid-day-events {
    margin: 0 1px !important;
  }

  .fc-theme-custom .fc-daygrid-event {
    padding: 1px 3px !important;
    font-size: 0.5625rem !important;
    line-height: 1.2 !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .fc-theme-custom .fc-daygrid-event-harness {
    margin-bottom: 1px !important;
  }

  .fc-theme-custom .fc-more-link {
    font-size: 0.625rem !important;
  }

  .fc-theme-custom .fc-daygrid-day-frame {
    min-height: 0 !important;
  }

  .fc-theme-custom .fc-daygrid-day-top {
    flex-direction: row !important;
  }

  .fc-theme-custom th .fc-scrollgrid-sync-inner {
    padding: 0.25rem 0.125rem !important;
    font-size: 0.6875rem !important;
  }

  .fc-theme-custom .fc-col-header-cell-cushion {
    font-size: 0.6875rem !important;
    padding: 2px !important;
  }

  /* Hide event time on mobile — show names only */
  .fc-theme-custom .fc-event-time {
    display: none !important;
  }

  .fc-theme-custom .fc-event-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
</style>