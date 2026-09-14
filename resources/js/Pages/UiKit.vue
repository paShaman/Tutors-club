<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Table from '@/components/ui/Table.vue'
import TableHeader from '@/components/ui/TableHeader.vue'
import TableBody from '@/components/ui/TableBody.vue'
import TableRow from '@/components/ui/TableRow.vue'
import TableHead from '@/components/ui/TableHead.vue'
import TableCell from '@/components/ui/TableCell.vue'
import Button from '@/components/ui/Button.vue'
import Tabs from '@/components/ui/Tabs.vue'
import type { TabItem } from '@/components/ui/Tabs.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import StudentAvatar from '@/components/ui/StudentAvatar.vue'
import StudentColorPicker from '@/components/ui/StudentColorPicker.vue'
import AvatarPicker from '@/components/ui/AvatarPicker.vue'
import ImageCropper from '@/components/ui/ImageCropper.vue'
import TopicStatusBadge from '@/components/ui/TopicStatusBadge.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import StudentFormPopup from '@/components/popups/StudentFormPopup.vue'
import type { StudentFormData } from '@/components/popups/StudentFormPopup.vue'
import LessonFormPopup from '@/components/popups/LessonFormPopup.vue'
import type { LessonFormData } from '@/components/popups/LessonFormPopup.vue'
import TopicFormPopup from '@/components/popups/TopicFormPopup.vue'
import type { TopicFormData } from '@/components/popups/TopicFormPopup.vue'
import ReviewFormPopup from '@/components/popups/ReviewFormPopup.vue'
import type { ReviewFormData } from '@/components/popups/ReviewFormPopup.vue'
import ChangelogModal from '@/components/popups/ChangelogModal.vue'
import RequisitesModal from '@/components/popups/RequisitesModal.vue'
import UiKitSection from '@/components/uikit/UiKitSection.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'

defineOptions({ layout: AppLayout })

const { t } = useI18n()
const toast = useToast()

// ─── Демо-состояние ─────────────────────────────────────────
const color = ref('violet')
const avatar = ref<string | null>(null)
const cropperSrc = ref<string | null>(null)

const showConfirm = ref(false)
const showStudent = ref(false)
const showLesson = ref(false)
const showTopic = ref(false)
const showReview = ref(false)
const showChangelog = ref(false)
const showRequisites = ref(false)

// ─── Токены ─────────────────────────────────────────────────
const colorTokens = [
  { name: 'background', class: 'bg-background' },
  { name: 'foreground', class: 'bg-foreground' },
  { name: 'card', class: 'bg-card' },
  { name: 'primary', class: 'bg-primary' },
  { name: 'primary-foreground', class: 'bg-primary-foreground' },
  { name: 'secondary', class: 'bg-secondary' },
  { name: 'muted', class: 'bg-muted' },
  { name: 'muted-foreground', class: 'bg-muted-foreground' },
  { name: 'accent', class: 'bg-accent' },
  { name: 'destructive', class: 'bg-destructive' },
  { name: 'border', class: 'bg-border' },
  { name: 'input', class: 'bg-input' },
  { name: 'ring', class: 'bg-ring' },
]

// ─── Кнопки ─────────────────────────────────────────────────
const buttonVariants = ['default', 'secondary', 'destructive', 'outline', 'ghost', 'link'] as const
const buttonSizes = ['sm', 'default', 'lg', 'icon'] as const

// ─── Табы ───────────────────────────────────────────────────
const activeTab = ref('stats')

const tabItems = computed<TabItem[]>(() => [
  { value: 'stats', label: t('ui.planning.tabs.stats') },
  { value: 'topics', label: t('ui.planning.tabs.topics') },
])

// ─── Демо-данные для попапов ────────────────────────────────
const sampleStudents = [
  { id: 1, name: 'Иван Петров', current_class: '9Б' },
  { id: 2, name: 'Аня Смирнова', current_class: '11А' },
]

const sampleSubjects = ['maths']

const sampleSubjectNames = computed<Record<string, string>>(() => ({
  maths: t('lesson_subject_maths'),
}))

const sampleTopicTree = {
  maths: [
    {
      id: 1,
      name: 'Квадратичная функция',
      children: [{ id: 2, name: 'График параболы', children: [] }],
    },
  ],
}

const tableRows = computed(() => [
  { id: 1, student: 'Иван Петров', subject: t('lesson_subject_maths'), date: '12.05.2026', status: 'mastered' },
  { id: 2, student: 'Аня Смирнова', subject: t('lesson_subject_english'), date: '14.05.2026', status: 'in_progress' },
  { id: 3, student: 'Пётр Кузнецов', subject: t('lesson_subject_informatics'), date: '16.05.2026', status: 'not_started' },
])

const demoImage = 'data:image/svg+xml;utf8,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="480" height="360">'
  + '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
  + '<stop offset="0" stop-color="#a78bfa"/><stop offset="1" stop-color="#7c3aed"/>'
  + '</linearGradient></defs><rect width="480" height="360" fill="url(#g)"/>'
  + '<circle cx="240" cy="150" r="80" fill="#ffffff" opacity="0.9"/>'
  + '<rect x="140" y="250" width="200" height="90" rx="45" fill="#ffffff" opacity="0.9"/></svg>',
)

function demoNotify(): void {
  toast.info(t('ui.uikit.toasts.message'))
}

function openCropper(): void {
  cropperSrc.value = demoImage
}

function onCropApply(): void {
  cropperSrc.value = null
  demoNotify()
}

// Заглушки обработчиков: на справочной странице формы ничего не отправляют.
function onSubmitStudent(_form: StudentFormData): void {
  showStudent.value = false
  demoNotify()
}

function onSubmitLesson(_form: LessonFormData): void {
  showLesson.value = false
  demoNotify()
}

function onSubmitTopic(_form: TopicFormData): void {
  showTopic.value = false
  demoNotify()
}

function onSubmitReview(_form: ReviewFormData): void {
  showReview.value = false
  demoNotify()
}

// ─── Примеры кода ───────────────────────────────────────────
const snippets = {
  tokens: [
    '/* resources/css/app.css */',
    '@theme {',
    '  --color-primary: hsl(252 87% 67%);',
    '  --radius: 0.75rem;',
    '}',
    '',
    '<div class="rounded-xl bg-primary text-primary-foreground">…</div>',
  ].join('\n'),

  button: [
    "<Button>{{ t('ui.common.save') }}</Button>",
    "<Button variant=\"outline\">{{ t('ui.common.cancel') }}</Button>",
    "<Button variant=\"destructive\" size=\"sm\">",
    '  <Trash2 class="h-4 w-4" />',
    "  {{ t('ui.common.delete') }}",
    '</Button>',
  ].join('\n'),

  card: [
    '<Card>',
    '  <CardHeader>',
    '    <CardTitle>{{ title }}</CardTitle>',
    '  </CardHeader>',
    '  <div class="px-6 pb-6 text-sm text-muted-foreground">',
    '    {{ body }}',
    '  </div>',
    '</Card>',
  ].join('\n'),

  table: [
    '<Card class="overflow-hidden p-0">',
    '  <Table>',
    '    <TableHeader>',
    '      <TableRow>',
    '        <TableHead>{{ header }}</TableHead>',
    '      </TableRow>',
    '    </TableHeader>',
    '    <TableBody>',
    '      <TableRow>',
    '        <TableCell>{{ value }}</TableCell>',
    '      </TableRow>',
    '    </TableBody>',
    '  </Table>',
    '</Card>',
  ].join('\n'),

  tabs: [
    'const items = [',
    "  { value: 'stats', label: t('ui.planning.tabs.stats') },",
    "  { value: 'topics', label: t('ui.planning.tabs.topics') },",
    ']',
    '',
    '<Tabs v-model="activeTab" :items="items" />',
  ].join('\n'),

  avatar: [
    '<UserAvatar :name="user.name" :src="user.avatar" class="h-10 w-10" />',
    '',
    '<StudentAvatar name="Аня" gender="girl" color="pink" class="h-12 w-12" />',
    '',
    '<StudentColorPicker v-model="color" />',
    '',
    '<AvatarPicker v-model="avatar" :name="user.name" />',
  ].join('\n'),

  badge: "<TopicStatusBadge status=\"mastered\" />",

  form: [
    '<label class="block text-sm font-medium text-foreground mb-1.5">Label</label>',
    '<input',
    '  v-model="value"',
    '  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm',
    '    text-foreground placeholder:text-muted-foreground focus:outline-none',
    '    focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"',
    '  :placeholder="t(\'ui.uikit.forms.input_placeholder\')"',
    '/>',
  ].join('\n'),

  toast: [
    'const toast = useToast()',
    '',
    "toast.success(t('success.settings'))",
    "toast.error(t('error.login'))",
    "toast.warning(t('ui.tariff.limit.students'))",
    "toast.info(t('ui.uikit.toasts.message'))",
  ].join('\n'),

  popup: [
    '<ConfirmDialog',
    '  :show="showConfirm"',
    "  :title=\"t('ui.uikit.popups.confirm_title')\"",
    "  :message=\"t('ui.uikit.popups.confirm_message')\"",
    '  variant="danger"',
    '  @confirm="onConfirm"',
    '  @cancel="showConfirm = false"',
    '/>',
  ].join('\n'),
}
</script>

<template>
  <Head :title="t('ui.uikit.title')" />

  <div class="animate-fade-up space-y-8">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ t('ui.uikit.title') }}</h1>
      <p class="mt-1 max-w-3xl text-sm text-muted-foreground">{{ t('ui.uikit.subtitle') }}</p>
    </div>

    <!-- Токены -->
    <UiKitSection :title="t('ui.uikit.sections.tokens')" :hint="t('ui.uikit.hints.tokens')" :code="snippets.tokens">
      <div class="space-y-6">
        <div>
          <p class="mb-2 text-sm font-medium text-foreground">{{ t('ui.uikit.token.colors') }}</p>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div v-for="token in colorTokens" :key="token.name" class="space-y-1.5">
              <div :class="cn('h-12 rounded-xl border border-border', token.class)" />
              <p class="text-xs text-muted-foreground">{{ token.name }}</p>
            </div>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <p class="mb-2 text-sm font-medium text-foreground">{{ t('ui.uikit.token.radius') }}</p>
            <div class="flex items-center gap-3">
              <div class="h-14 w-14 rounded-xl border border-border bg-primary/10" />
              <div class="h-14 w-14 rounded-2xl border border-border bg-primary/10" />
              <div class="h-14 w-14 rounded-full border border-border bg-primary/10" />
            </div>
            <p class="mt-2 text-xs text-muted-foreground">{{ t('ui.uikit.token.radius_hint') }}</p>
          </div>

          <div>
            <p class="mb-2 text-sm font-medium text-foreground">{{ t('ui.uikit.token.glass') }}</p>
            <div class="glass flex h-14 items-center rounded-xl px-4 text-sm text-foreground">
              {{ t('ui.uikit.token.glass_hint') }}
            </div>
          </div>
        </div>

        <div>
          <p class="mb-2 text-sm font-medium text-foreground">{{ t('ui.uikit.token.fonts') }}</p>
          <p class="text-sm text-foreground">{{ t('ui.uikit.token.font_sans') }}</p>
          <p class="font-mono text-sm text-foreground">{{ t('ui.uikit.token.font_mono') }}</p>
        </div>
      </div>
    </UiKitSection>

    <!-- Кнопки -->
    <UiKitSection :title="t('ui.uikit.sections.buttons')" :hint="t('ui.uikit.hints.buttons')" :code="snippets.button">
      <div class="space-y-6">
        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.buttons.variants') }}
          </p>
          <div class="flex flex-wrap items-center gap-3">
            <Button v-for="variant in buttonVariants" :key="variant" :variant="variant">
              {{ variant }}
            </Button>
          </div>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.buttons.sizes') }}
          </p>
          <div class="flex flex-wrap items-center gap-3">
            <Button v-for="size in buttonSizes" :key="size" :size="size">
              <Plus v-if="size === 'icon'" class="h-4 w-4" />
              <template v-else>{{ size }}</template>
            </Button>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-6">
          <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {{ t('ui.uikit.buttons.disabled') }}
            </p>
            <Button disabled>{{ t('ui.common.save') }}</Button>
          </div>
          <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {{ t('ui.uikit.buttons.with_icon') }}
            </p>
            <Button>
              <Plus class="h-4 w-4" />
              {{ t('ui.common.add') }}
            </Button>
          </div>
        </div>
      </div>
    </UiKitSection>

    <!-- Табы -->
    <UiKitSection :title="t('ui.uikit.sections.tabs')" :hint="t('ui.uikit.hints.tabs')" :code="snippets.tabs">
      <Tabs v-model="activeTab" :items="tabItems" />
    </UiKitSection>

    <!-- Карточки -->
    <UiKitSection :title="t('ui.uikit.sections.cards')" :hint="t('ui.uikit.hints.cards')" :code="snippets.card">
      <Card>
        <CardHeader>
          <CardTitle>{{ t('ui.uikit.cards.header') }}</CardTitle>
        </CardHeader>
        <div class="px-6 pb-6 text-sm text-muted-foreground">
          {{ t('ui.uikit.cards.body') }}
        </div>
      </Card>
    </UiKitSection>

    <!-- Таблицы -->
    <UiKitSection :title="t('ui.uikit.sections.tables')" :hint="t('ui.uikit.hints.tables')" :code="snippets.table">
      <Card class="overflow-hidden p-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>{{ t('ui.uikit.table.student') }}</TableHead>
              <TableHead>{{ t('ui.uikit.table.subject') }}</TableHead>
              <TableHead>{{ t('ui.uikit.table.date') }}</TableHead>
              <TableHead>{{ t('ui.uikit.table.status') }}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="row in tableRows" :key="row.id">
              <TableCell>{{ row.student }}</TableCell>
              <TableCell>{{ row.subject }}</TableCell>
              <TableCell>{{ row.date }}</TableCell>
              <TableCell>
                <TopicStatusBadge :status="row.status" />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </Card>
    </UiKitSection>

    <!-- Аватары -->
    <UiKitSection :title="t('ui.uikit.sections.avatars')" :hint="t('ui.uikit.hints.avatars')" :code="snippets.avatar">
      <div class="space-y-6">
        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.avatars.user') }}
          </p>
          <div class="flex flex-wrap items-center gap-4">
            <UserAvatar name="Tutors Club" class="h-8 w-8 bg-gradient-to-br from-primary to-purple-500 text-xs font-semibold text-white shadow" />
            <UserAvatar name="Tutors Club" class="h-12 w-12 bg-gradient-to-br from-primary to-purple-500 text-base font-semibold text-white shadow" />
            <UserAvatar name="Tutors Club" class="h-16 w-16 bg-gradient-to-br from-primary to-purple-500 text-xl font-semibold text-white shadow" />
          </div>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.avatars.student') }}
          </p>
          <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-2">
              <StudentAvatar name="Иван" gender="boy" color="sky" class="h-12 w-12" />
              <span class="text-xs text-muted-foreground">{{ t('ui.uikit.avatars.boy') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <StudentAvatar name="Аня" gender="girl" color="pink" class="h-12 w-12" />
              <span class="text-xs text-muted-foreground">{{ t('ui.uikit.avatars.girl') }}</span>
            </div>
            <div class="flex items-center gap-2">
              <StudentAvatar name="Группа" gender="none" color="slate" class="h-12 w-12" />
              <span class="text-xs text-muted-foreground">{{ t('ui.uikit.avatars.none') }}</span>
            </div>
          </div>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.avatars.colors') }}
          </p>
          <StudentColorPicker v-model="color" />
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.avatars.picker') }}
          </p>
          <AvatarPicker v-model="avatar" name="Tutors Club" />
        </div>
      </div>
    </UiKitSection>

    <!-- Статусы -->
    <UiKitSection :title="t('ui.uikit.sections.badges')" :hint="t('ui.uikit.hints.badges')" :code="snippets.badge">
      <div class="flex flex-wrap items-center gap-3">
        <TopicStatusBadge status="mastered" />
        <TopicStatusBadge status="in_progress" />
        <TopicStatusBadge status="not_started" />
      </div>
    </UiKitSection>

    <!-- Формы -->
    <UiKitSection :title="t('ui.uikit.sections.forms')" :hint="t('ui.uikit.hints.forms')" :code="snippets.form">
      <div class="grid max-w-2xl gap-5">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-foreground">{{ t('ui.uikit.forms.input') }}</label>
          <input
            class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors"
            :placeholder="t('ui.uikit.forms.input_placeholder')"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-foreground">{{ t('ui.uikit.forms.textarea') }}</label>
          <textarea
            rows="3"
            class="w-full resize-none rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors"
            :placeholder="t('ui.uikit.forms.textarea_placeholder')"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-foreground">{{ t('ui.uikit.forms.select') }}</label>
          <select class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors">
            <option value="" disabled selected>{{ t('ui.uikit.forms.select_placeholder') }}</option>
            <option value="1">{{ t('ui.uikit.forms.optional') }}</option>
          </select>
        </div>

        <label class="flex cursor-pointer items-center gap-2">
          <input type="checkbox" class="rounded border-border text-primary focus:ring-primary/30" />
          <span class="text-sm text-foreground">{{ t('ui.uikit.forms.checkbox') }}</span>
        </label>
      </div>
    </UiKitSection>

    <!-- Тосты -->
    <UiKitSection :title="t('ui.uikit.sections.toasts')" :hint="t('ui.uikit.hints.toasts')" :code="snippets.toast">
      <div class="flex flex-wrap items-center gap-3">
        <Button variant="default" @click="toast.success(t('ui.uikit.toasts.message'))">
          {{ t('ui.uikit.toasts.success') }}
        </Button>
        <Button variant="destructive" @click="toast.error(t('ui.uikit.toasts.message'))">
          {{ t('ui.uikit.toasts.error') }}
        </Button>
        <Button variant="secondary" @click="toast.warning(t('ui.uikit.toasts.message'))">
          {{ t('ui.uikit.toasts.warning') }}
        </Button>
        <Button variant="outline" @click="toast.info(t('ui.uikit.toasts.message'))">
          {{ t('ui.uikit.toasts.info') }}
        </Button>
      </div>
    </UiKitSection>

    <!-- Попапы -->
    <UiKitSection :title="t('ui.uikit.sections.popups')" :hint="t('ui.uikit.hints.popups')" :code="snippets.popup">
      <div class="flex flex-wrap items-center gap-3">
        <Button variant="outline" @click="showConfirm = true">
          {{ t('ui.uikit.popups.confirm') }}
        </Button>
        <Button variant="outline" @click="showStudent = true">
          {{ t('ui.uikit.popups.student') }}
        </Button>
        <Button variant="outline" @click="showLesson = true">
          {{ t('ui.uikit.popups.lesson') }}
        </Button>
        <Button variant="outline" @click="showTopic = true">
          {{ t('ui.uikit.popups.topic') }}
        </Button>
        <Button variant="outline" @click="showReview = true">
          {{ t('ui.uikit.popups.review') }}
        </Button>
        <Button variant="outline" @click="showChangelog = true">
          {{ t('ui.uikit.popups.changelog') }}
        </Button>
        <Button variant="outline" @click="showRequisites = true">
          {{ t('ui.uikit.popups.requisites') }}
        </Button>
        <Button variant="outline" @click="openCropper">
          {{ t('ui.uikit.popups.cropper') }}
        </Button>
      </div>
    </UiKitSection>
  </div>

  <!-- Попапы -->
  <ConfirmDialog
    :show="showConfirm"
    :title="t('ui.uikit.popups.confirm_title')"
    :message="t('ui.uikit.popups.confirm_message')"
    variant="danger"
    @confirm="showConfirm = false"
    @cancel="showConfirm = false"
  />

  <StudentFormPopup
    :show="showStudent"
    mode="add"
    @close="showStudent = false"
    @submit="onSubmitStudent"
  />

  <LessonFormPopup
    :show="showLesson"
    mode="add"
    :students="sampleStudents"
    :subjects="sampleSubjects"
    :subject-names="sampleSubjectNames"
    :topic-tree="sampleTopicTree"
    :topic-statuses="{}"
    :default-price="3000"
    :default-duration="60"
    :can-add-topic="true"
    @close="showLesson = false"
    @submit="onSubmitLesson"
    @delete="showLesson = false"
  />

  <TopicFormPopup
    :show="showTopic"
    mode="add"
    subject="maths"
    :initial="null"
    @close="showTopic = false"
    @submit="onSubmitTopic"
  />

  <ReviewFormPopup
    :show="showReview"
    :topic-name="t('ui.uikit.popups.review_topic')"
    @close="showReview = false"
    @submit="onSubmitReview"
  />

  <ChangelogModal :show="showChangelog" @close="showChangelog = false" />

  <RequisitesModal :show="showRequisites" @close="showRequisites = false" />

  <ImageCropper
    v-if="cropperSrc"
    :src="cropperSrc"
    @cancel="cropperSrc = null"
    @apply="onCropApply"
  />
</template>
