<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import ruLocale from '@fullcalendar/core/locales/ru'
import type { EventClickArg, DatesSetArg } from '@fullcalendar/core'

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

interface LessonEvent {
  student_id: number
  student_name: string
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

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const events = ref<any[]>([])

// Modal state
const showModal = ref(false)
const modalMode = ref<'add' | 'edit'>('edit')
const deletingLesson = ref<number | null>(null)

const subjectLabels: Record<string, string> = {
  lesson_subject_maths: 'Математика',
  lesson_subject_informatics: 'Информатика',
  lesson_subject_english: 'Английский',
}

function subjectName(key: string): string {
  return subjectLabels[key] ?? key
}

const form = ref({
  lesson_id: null as number | null,
  lesson_student_id: '' as string,
  lesson_subject: '',
  lesson_theme: '',
  lesson_price: page.props.defaultPrice ?? 3000,
  lesson_duration: page.props.defaultDuration ?? 60,
  lesson_date: '',
  lesson_time: '',
  lesson_date_payed: '',
  lesson_is_payed: false,
  lesson_is_future: false,
})

function handleEventClick(arg: EventClickArg) {
  const props = arg.event.extendedProps as LessonEvent

  modalMode.value = 'edit'
  form.value = {
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

  showModal.value = true
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

function closeModal() {
  showModal.value = false
}

async function submitLesson() {
  try {
    const response = await fetch('/lessons/edit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(form.value),
    })
    const data = await response.json()

    if (data.success) {
      closeModal()
      // Reload calendar events
      const calendarApi = calendarRef.value?.getApi()
      if (calendarApi) {
        calendarApi.refetchEvents()
      }
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}

async function deleteLesson() {
  if (!form.value.lesson_id) return
  if (!confirm('Удалить урок?')) return

  try {
    const response = await fetch('/lessons/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ lesson_id: form.value.lesson_id }),
    })
    const data = await response.json()

    if (data.success) {
      closeModal()
      const calendarApi = calendarRef.value?.getApi()
      if (calendarApi) {
        calendarApi.refetchEvents()
      }
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}
</script>

<template>
  <Head title="Календарь" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold tracking-tight text-foreground">
          Календарь
        </h1>
      </div>
    </div>

    <!-- Calendar -->
    <Card class="p-4 fc-theme-custom">
      <FullCalendar
        ref="calendarRef"
        :options="{
          plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
          initialView: 'dayGridMonth',
          locales: [ruLocale],
          locale: 'ru',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
          },
          buttonText: {
            today: 'Сегодня',
            month: 'Месяц',
            week: 'Неделя',
            day: 'День',
          },
          events: events,
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
        }"
      />
    </Card>

    <!-- Edit Modal Overlay -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="showModal" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="closeModal" />
      </Transition>
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-70 flex items-center justify-center p-4 overflow-y-auto">
          <Card class="relative w-full max-w-lg p-6 shadow-xl">
            <h2 class="text-2xl font-semibold text-foreground mb-5">
              Редактировать урок
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
                    {{ student.name }}{{ student.current_class ? ` ${student.current_class}` : '' }}
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
                  Сохранить
                </Button>
                <Button type="button" variant="destructive" class="flex-1" @click="deleteLesson">
                  Удалить
                </Button>
                <Button type="button" variant="outline" class="flex-1" @click="closeModal">
                  Отмена
                </Button>
              </div>
            </form>
          </Card>
        </div>
      </Transition>
    </Teleport>
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
