<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import type { EventClickArg, EventInput } from '@fullcalendar/core'
import ruLocale from '@fullcalendar/core/locales/ru'
import { ref, onMounted } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

const page = usePage<{ url: string }>()

const loading = ref(true)
const events = ref<EventInput[]>([])

async function fetchEvents(fetchInfo: { start: Date; end: Date; startStr: string; endStr: string }, successCallback: (events: EventInput[]) => void, failureCallback: (error: Error) => void) {
  try {
    const url = new URL('/calendar/events', page.props.url ? new URL(page.props.url).origin : window.location.origin)
    url.searchParams.set('start', fetchInfo.startStr)
    url.searchParams.set('end', fetchInfo.endStr)

    const response = await fetch(url.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) throw new Error('Network response was not ok')

    const data = await response.json()
    events.value = data
    successCallback(data)
  } catch (error) {
    failureCallback(error as Error)
  } finally {
    loading.value = false
  }
}

function handleEventClick(info: EventClickArg) {
  // Will be used later for lesson editing
  console.log('Event clicked:', info.event.id, info.event.title)
}
</script>

<template>
  <Head title="Календарь" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center gap-3 sm:gap-4">
      <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10">
        <CalendarIcon class="h-5 w-5 sm:h-6 sm:w-6 text-primary" />
      </div>
      <div class="min-w-0">
        <h1 class="text-lg sm:text-2xl font-bold tracking-tight text-foreground">
          Календарь уроков
        </h1>
        <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
          Расписание занятий с учениками
        </p>
      </div>
    </div>

    <!-- Calendar Card -->
    <Card class="p-2 sm:p-4 md:p-6 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-16 text-muted-foreground text-sm">
        Загрузка календаря...
      </div>

      <FullCalendar
        :options="{
          plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
          initialView: 'dayGridMonth',
          locales: [ruLocale],
          locale: 'ru',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek',
          },
          buttonText: {
            today: 'Сегодня',
            month: 'Месяц',
            week: 'Неделя',
          },
          events: fetchEvents,
          eventClick: handleEventClick,
          height: 'auto',
          firstDay: 1,
          eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
          },
          slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
          },
        }"
        class="fc-theme-custom"
      />
    </Card>
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
  border-radius: 0.375rem;
  padding: 2px 6px;
  font-size: 0.75rem;
  cursor: pointer;
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
