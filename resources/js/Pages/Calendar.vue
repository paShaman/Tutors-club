<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import enLocale from '@fullcalendar/core/locales/en-gb'
import type { CalendarOptions, EventClickArg, DatesSetArg } from '@fullcalendar/core'
import LessonFormPopup from '@/components/popups/LessonFormPopup.vue'
import type { LessonFormData } from '@/components/popups/LessonFormPopup.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'

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
  defaultPrice: number
  defaultDuration: number
  defaultDate: string
}>()

const toast = useToast()
const { t, locale } = useI18n()

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const events = ref<any[]>([])

const fullCalendarLocale = computed(() => FC_LOCALES[locale.value] ?? ruLocale)

const calendarOptions = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locales: [fullCalendarLocale.value],
  locale: fullCalendarLocale.value,
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  buttonText: {
    today: t('ui.calendar.today'),
    month: t('ui.calendar.month'),
    week: t('ui.calendar.week'),
    day: t('ui.calendar.day'),
  },
  events: events.value,
  eventClick: handleEventClick,
  datesSet: handleDatesSet,
  editable: false,
  selectable: false,
  firstDay: 1,
  height: 'auto',
  eventTimeFormat: {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  },
}))

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
    lesson_theme: props.theme ?? '',
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

function handleDatesSet(arg: DatesSetArg) {
  const view = arg.view

  fetch('/calendar/events?' + new URLSearchParams({
    start: view.activeStart.toISOString(),
    end: view.activeEnd.toISOString(),
  }))
    .then(res => res.json())
    .then(data => {
      events.value = data
    })
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

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          {{ t('ui.calendar.title') }}
        </h1>
      </div>
    </div>

    <!-- Calendar -->
    <Card class="p-4 fc-theme-custom">
      <FullCalendar
        ref="calendarRef"
        :options="calendarOptions"
      />
    </Card>

    <!-- Lesson Form Popup -->
    <LessonFormPopup
      :show="showLessonPopup"
      mode="edit"
      :students="page.props.students.map(s => ({ id: s.id, name: s.name, current_class: s.current_class }))"
      :subjects="page.props.lessonsSubjects"
      :defaultPrice="page.props.defaultPrice"
      :defaultDuration="page.props.defaultDuration"
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
  --fc-button-bg-color: hsl(252 87% 67%);
  --fc-button-border-color: hsl(252 87% 67%);
  --fc-button-hover-bg-color: hsl(252 87% 60%);
  --fc-button-hover-border-color: hsl(252 87% 60%);
  --fc-button-active-bg-color: hsl(252 87% 55%);
  --fc-button-active-border-color: hsl(252 87% 55%);
}

.fc-theme-custom .fc-button {
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 0.625rem;
  text-transform: capitalize;
}

.fc-theme-custom .fc-toolbar-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: hsl(222.2 47.4% 11.2%);
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

/* --- Mobile: toolbar in two rows --- */
@media (max-width: 639px) {
  .fc-theme-custom .fc-toolbar {
    flex-direction: column !important;
    gap: 0.5rem !important;
  }

  .fc-theme-custom .fc-toolbar-title {
    font-size: 0.9375rem !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .fc-theme-custom .fc-button {
    padding: 0.375rem 0.625rem !important;
    font-size: 0.6875rem !important;
    border-radius: 0.5rem !important;
  }

  .fc-theme-custom .fc-toolbar-chunk {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    justify-content: center;
  }

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