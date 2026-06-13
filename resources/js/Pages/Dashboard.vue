<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import { cn } from '@/lib/utils'
import {
  Clock,
  Calendar,
  GraduationCap,
  TrendingUp,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  ChevronRight,
} from 'lucide-vue-next'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Filler,
} from 'chart.js'
import { computed } from 'vue'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Filler,
)

defineOptions({ layout: AppLayout })

const props = defineProps<{
  userName?: string
  nextLesson: {
    id: number
    studentName: string
    studentClass: string
    date: string
    time: string
    duration: number
    isPaid: boolean
    isUpcoming: boolean
  } | null
  students: Array<{
    id: number
    name: string
    studentClass: string
    totalLessons: number
    paidLessons: number
    avatarColor: string
  }>
  chartData: Array<{
    day: string
    hours: number
  }>
  earningsByMonth: Array<{
    month: string
    amount: number
  }>
  totalEarnings: number
  todaysLessonsCount: number
}>()

const totalHoursThisWeek = computed(() =>
  props.chartData.reduce((sum, d) => sum + d.hours, 0),
)

const paidPercent = computed(() => {
  if (!props.students.length) return 0
  const total = props.students.reduce((s, st) => s + st.totalLessons, 0)
  const paid = props.students.reduce((s, st) => s + st.paidLessons, 0)
  return total > 0 ? Math.round((paid / total) * 100) : 0
})

const chartJsData = computed(() => ({
  labels: props.chartData.map(d => d.day),
  datasets: [
    {
      label: 'Часы',
      data: props.chartData.map(d => d.hours),
      borderColor: 'hsl(252 87% 67%)',
      backgroundColor: (ctx: any) => {
        if (!ctx.chart?.chartArea) return 'transparent'
        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.chartArea.bottom)
        gradient.addColorStop(0, 'hsla(252, 87%, 67%, 0.35)')
        gradient.addColorStop(1, 'hsla(252, 87%, 67%, 0)')
        return gradient
      },
      fill: true,
      tension: 0.4,
      pointBackgroundColor: 'hsl(252 87% 67%)',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
      pointHoverBorderWidth: 3,
    },
  ],
}))

const chartJsOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#fff',
      titleColor: '#1e293b',
      bodyColor: '#475569',
      borderColor: 'hsl(214.3 31.8% 91.4%)',
      borderWidth: 1,
      cornerRadius: 12,
      padding: 12,
      displayColors: false,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: 'hsl(215.4 16.3% 46.9%)', font: { size: 12 } },
    },
    y: {
      grid: { color: 'hsl(214.3 31.8% 91.4%)', drawBorder: false },
      ticks: { color: 'hsl(215.4 16.3% 46.9%)', font: { size: 12 } },
    },
  },
}))

// Bar chart — earnings by month
const barChartData = computed(() => ({
  labels: props.earningsByMonth.map(d => d.month),
  datasets: [
    {
      label: 'Заработок',
      data: props.earningsByMonth.map(d => d.amount),
      backgroundColor: 'hsl(252 87% 67%)',
      borderRadius: 6,
      borderSkipped: false,
      hoverBackgroundColor: 'hsl(252 87% 60%)',
    },
  ],
}))

const barChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#fff',
      titleColor: '#1e293b',
      bodyColor: '#475569',
      borderColor: 'hsl(214.3 31.8% 91.4%)',
      borderWidth: 1,
      cornerRadius: 12,
      padding: 12,
      displayColors: false,
      callbacks: {
        label: (ctx: any) => `${ctx.raw.toLocaleString('ru-RU')} ₽`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: 'hsl(215.4 16.3% 46.9%)', font: { size: 12 } },
    },
    y: {
      grid: { color: 'hsl(214.3 31.8% 91.4%)', drawBorder: false },
      ticks: {
        color: 'hsl(215.4 16.3% 46.9%)',
        font: { size: 12 },
        callback: (v: any) => `${v.toLocaleString('ru-RU')} ₽`,
      },
    },
  },
}))
</script>

<template>
  <Head title="Дашборд" />

  <div class="space-y-8 animate-fade-up">
    <!-- Page header -->
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-foreground">
        Добрый день, {{ userName ?? 'коллега' }} 👋
      </h1>
      <p class="mt-1 text-muted-foreground">
        Вот сводка на сегодня
      </p>
    </div>

    <!-- Stats mini-cards -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card class="p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
              Часов на этой неделе
            </p>
            <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground whitespace-nowrap">
              {{ totalHoursThisWeek }}
            </p>
          </div>
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10">
            <Clock class="h-5 w-5 shrink-0 text-primary" />
          </div>
        </div>
      </Card>

      <Card class="p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
              Активных учеников
            </p>
            <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground whitespace-nowrap">
              {{ students.length }}
            </p>
          </div>
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10">
            <GraduationCap class="h-5 w-5 shrink-0 text-emerald-600" />
          </div>
        </div>
      </Card>

      <Card class="p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
              Заработано за месяц
            </p>
            <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground whitespace-nowrap">
              {{ totalEarnings.toLocaleString('ru-RU') }} ₽
            </p>
          </div>
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-500/10">
            <span class="text-xl font-bold text-blue-600">₽</span>
          </div>
        </div>
      </Card>

      <Card class="p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
              Оплачено уроков
            </p>
            <p class="mt-2 text-2xl xl:text-3xl font-bold text-foreground whitespace-nowrap">
              {{ paidPercent }}%
            </p>
          </div>
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10">
            <TrendingUp class="h-5 w-5 shrink-0 text-amber-600" />
          </div>
        </div>
      </Card>
    </div>

    <!-- Main grid: Next lesson + Students -->
    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Next lesson card (takes 2 cols) -->
      <Card v-if="nextLesson" class="lg:col-span-2 overflow-hidden">
        <div class="bg-gradient-to-br from-primary/5 via-primary/10 to-purple-500/5 p-6">
          <div class="flex items-center gap-2 text-sm font-medium text-primary mb-1">
            <Calendar class="h-4 w-4" />
            {{ nextLesson.isUpcoming ? 'Следующий урок' : 'Последний урок' }}
          </div>
          <div class="flex items-start justify-between mt-1">
            <div>
              <h3 class="text-xl font-semibold text-foreground">
                {{ nextLesson.studentName }}
              </h3>
              <p class="text-sm text-muted-foreground mt-0.5">
                {{ nextLesson.studentClass }} · {{ nextLesson.duration }} мин
              </p>
            </div>
            <span
              :class="cn(
                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                nextLesson.isPaid
                  ? 'bg-emerald-100 text-emerald-700'
                  : 'bg-red-100 text-red-700',
              )"
            >
              <CheckCircle2 v-if="nextLesson.isPaid" class="h-3 w-3" />
              <AlertCircle v-else class="h-3 w-3" />
              {{ nextLesson.isPaid ? 'Оплачен' : 'Не оплачен' }}
            </span>
          </div>
          <div class="mt-6 flex items-center gap-6">
            <div class="flex items-center gap-2 text-2xl font-bold text-foreground">
              <Clock class="h-5 w-5 text-muted-foreground" />
              {{ nextLesson.time }}
            </div>
            <div class="text-sm text-muted-foreground">
              {{ nextLesson.date }}
            </div>
          </div>
        </div>
        <div class="border-t border-border p-4 flex items-center justify-between">
          <span class="text-sm text-muted-foreground">
            Всего сегодня уроков: <strong>{{ todaysLessonsCount }}</strong>
          </span>
          <Link href="/calendar" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-foreground transition-colors">
            Календарь <ArrowRight class="h-4 w-4" />
          </Link>
        </div>
      </Card>

      <!-- No upcoming lessons placeholder -->
      <Card v-else class="lg:col-span-2 p-6 flex items-center justify-center">
        <p class="text-muted-foreground text-sm">Нет предстоящих уроков</p>
      </Card>

      <!-- Students mini grid (takes 3 cols) -->
      <div class="lg:col-span-3 grid gap-4 sm:grid-cols-2">
        <Card v-for="student in students" :key="student.id" class="p-5 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-center gap-4">
            <div
              :class="cn(
                'flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br text-sm font-semibold text-white shadow shrink-0',
                student.avatarColor,
              )"
            >
              {{ student.name.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-foreground truncate">{{ student.name }}</p>
              <p class="text-xs text-muted-foreground">{{ student.studentClass }}</p>
            </div>
              <Link :href="`/lessons?student_id=${student.id}`" class="inline-flex items-center justify-center rounded-lg h-9 w-9 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors shrink-0">
                <ChevronRight class="h-4 w-4" />
              </Link>
          </div>
          <div class="mt-4 flex items-center gap-4 text-sm">
            <div>
            <p class="text-muted-foreground">За месяц</p>
              <p class="font-semibold text-foreground">{{ student.totalLessons }}</p>
            </div>
            <div class="h-8 w-px bg-border" />
            <div>
              <p class="text-muted-foreground">Оплачено</p>
              <p class="font-semibold text-emerald-600">{{ student.paidLessons }}</p>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- Charts row -->
    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Load chart (3 cols) -->
      <Card class="lg:col-span-3">
        <CardHeader>
          <CardTitle>Нагрузка (часы)</CardTitle>
          <p class="text-sm text-muted-foreground">Текущая неделя</p>
        </CardHeader>
        <div class="px-2 pb-4">
          <div class="h-[240px]">
            <Line :data="chartJsData" :options="chartJsOptions" />
          </div>
        </div>
      </Card>

      <!-- Earnings bar chart (2 cols) -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Заработок</CardTitle>
          <p class="text-sm text-muted-foreground">{{ new Date().getFullYear() }} год</p>
        </CardHeader>
        <div class="px-2 pb-4">
          <div class="h-[240px]">
            <Bar :data="barChartData" :options="barChartOptions" />
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>