<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, RotateCcw } from 'lucide-vue-next'
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
import IconButton from '@/components/ui/IconButton.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import Radio from '@/components/ui/Radio.vue'
import Select from '@/components/ui/Select.vue'
import type { SelectOption } from '@/components/ui/Select.vue'
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

const formSelect = ref<string | null>(null)
const formCheckbox = ref(false)
const formRadio = ref('first')

const formSelectOptions = computed<SelectOption<string>[]>(() => [
  { value: 'first', label: t('ui.uikit.forms.option_first') },
  { value: 'second', label: t('ui.uikit.forms.option_second') },
  { value: 'third', label: t('ui.uikit.forms.option_third') },
])

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
    '',
    '<!-- Иконки-действия в списках/карточках: правка, удаление, восстановление -->',
    '<IconButton :title="t(\'ui.common.edit\')"><Pencil class="h-4 w-4" /></IconButton>',
    '<IconButton variant="destructive" :title="t(\'ui.common.delete\')">',
    '  <Trash2 class="h-4 w-4" />',
    '</IconButton>',
    '<IconButton variant="success" :title="t(\'ui.common.restore\')">',
    '  <RotateCcw class="h-4 w-4" />',
    '</IconButton>',
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
    '<label class="field-label">Label</label>',
    '<input v-model="value" class="field w-full px-3.5 py-2.5" />',
    '',
    '<Select v-model="choice" :options="options" :placeholder="..." />',
    '',
    '<Checkbox v-model="checked">{{ label }}</Checkbox>',
    '',
    '<Radio v-model="choice" name="group" value="first">{{ label }}</Radio>',
    '<Radio v-model="choice" name="group" value="second">{{ label }}</Radio>',
  ].join('\n'),

  helpers: [
    '<!-- Попап: задник + слой прокрутки + поверхность -->',
    '<div class="popup-overlay z-60" @click="close" />',
    '<div class="popup-layer z-70">',
    '  <Card class="popup-card max-w-md">',
    '    <h2 class="popup-title mb-5">Заголовок</h2>',
    '    <label class="field-label">Имя</label>',
    '    <input class="field w-full px-3.5 py-2.5" />',
    '    <p class="field-error">Ошибка</p>',
    '  </Card>',
    '</div>',
    '',
    '<!-- Шапка страницы: заголовок + кнопки-действия -->',
    '<div class="page-header">',
    '  <h1 class="page-title text-3xl">Заголовок</h1>',
    '  <div class="page-header-actions">',
    '    <Button variant="outline">Отмена</Button>',
    '    <Button>Добавить</Button>',
    '  </div>',
    '</div>',
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

  <div class="animate-fade-up space-y-8">    <div>
      <h1 class="page-title text-2xl">{{ t('ui.uikit.title') }}</h1>
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

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.buttons.icon_actions') }}
          </p>
          <div class="flex flex-wrap items-center gap-2">
            <IconButton :title="t('ui.common.edit')">
              <Pencil class="h-4 w-4" />
            </IconButton>
            <IconButton variant="destructive" :title="t('ui.common.delete')">
              <Trash2 class="h-4 w-4" />
            </IconButton>
            <IconButton variant="success" :title="t('ui.common.restore')">
              <RotateCcw class="h-4 w-4" />
            </IconButton>
            <IconButton variant="primary" :title="t('ui.common.add')">
              <Plus class="h-4 w-4" />
            </IconButton>
            <IconButton disabled :title="t('ui.uikit.buttons.disabled')">
              <Pencil class="h-4 w-4" />
            </IconButton>
          </div>
          <p class="mt-2 text-xs text-muted-foreground">{{ t('ui.uikit.buttons.icon_actions_hint') }}</p>
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
          <label class="field-label">{{ t('ui.uikit.forms.input') }}</label>
          <input
            class="field w-full px-3.5 py-2.5"
            :placeholder="t('ui.uikit.forms.input_placeholder')"
          />
        </div>

        <div>
          <label class="field-label">{{ t('ui.uikit.forms.textarea') }}</label>
          <textarea
            rows="3"
            class="field w-full resize-none px-3.5 py-2.5"
            :placeholder="t('ui.uikit.forms.textarea_placeholder')"
          />
        </div>

        <div>
          <label class="field-label">{{ t('ui.uikit.forms.select') }}</label>
          <Select
            v-model="formSelect"
            :options="formSelectOptions"
            :placeholder="t('ui.uikit.forms.select_placeholder')"
          />
          <p class="mt-1.5 text-xs text-muted-foreground">{{ t('ui.uikit.forms.select_hint') }}</p>
        </div>

        <div class="space-y-2">
          <p class="field-label mb-0">{{ t('ui.uikit.forms.checkbox') }}</p>
          <Checkbox v-model="formCheckbox">{{ t('ui.uikit.forms.checkbox_label') }}</Checkbox>
          <Checkbox :model-value="true" disabled>{{ t('ui.uikit.forms.disabled') }}</Checkbox>
        </div>

        <div class="space-y-2">
          <p class="field-label mb-0">{{ t('ui.uikit.forms.radio') }}</p>
          <div class="flex flex-wrap items-center gap-5">
            <Radio v-model="formRadio" name="uikit-radio" value="first">
              {{ t('ui.uikit.forms.option_first') }}
            </Radio>
            <Radio v-model="formRadio" name="uikit-radio" value="second">
              {{ t('ui.uikit.forms.option_second') }}
            </Radio>
            <Radio :model-value="'third'" name="uikit-radio" value="third" disabled>
              {{ t('ui.uikit.forms.disabled') }}
            </Radio>
          </div>
        </div>
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

    <!-- Общие классы -->
    <UiKitSection :title="t('ui.uikit.sections.helpers')" :hint="t('ui.uikit.hints.helpers')" :code="snippets.helpers">
      <div class="space-y-6">
        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.helpers.popups') }}
          </p>
          <p class="text-sm text-muted-foreground">{{ t('ui.uikit.helpers.popups_items') }}</p>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.helpers.forms') }}
          </p>
          <div class="grid max-w-2xl gap-3">
            <input class="field w-full px-3.5 py-2.5" :placeholder="t('ui.uikit.helpers.forms_items')" />
            <p class="field-error">{{ t('ui.uikit.helpers.forms_items') }}</p>
          </div>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.helpers.misc') }}
          </p>
          <div class="flex flex-wrap items-center gap-3">
            <span class="page-title text-2xl">{{ t('ui.uikit.helpers.misc') }}</span>
            <span class="pill bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
              {{ t('ui.uikit.helpers.misc_items') }}
            </span>
          </div>
        </div>

        <div>
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ t('ui.uikit.helpers.header') }}
          </p>
          <div class="rounded-xl border border-dashed border-border p-4">
            <div class="page-header">
              <span class="page-title text-xl">{{ t('ui.uikit.helpers.header_title') }}</span>
              <div class="page-header-actions">
                <Button variant="outline">{{ t('ui.common.cancel') }}</Button>
                <Button>{{ t('ui.common.add') }}</Button>
              </div>
            </div>
          </div>
          <p class="mt-2 text-sm text-muted-foreground">{{ t('ui.uikit.helpers.header_items') }}</p>
        </div>
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
