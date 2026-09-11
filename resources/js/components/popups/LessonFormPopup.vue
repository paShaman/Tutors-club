<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TopicFormPopup from '@/components/popups/TopicFormPopup.vue'
import type { TopicFormData } from '@/components/popups/TopicFormPopup.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()
const toast = useToast()

export interface StudentOption {
  id: number
  name: string
  current_class: string
}

export interface TopicNode {
  id: number
  name: string
  children: TopicNode[]
}

export type LessonFormData = {
  lesson_id: number | null
  lesson_student_id: string
  lesson_subject: string
  lesson_topic_id: number | null
  lesson_subtopic_id: number | null
  lesson_topic_status: string
  lesson_comment: string
  lesson_price: number
  lesson_duration: number
  lesson_date: string
  lesson_time: string
  lesson_date_payed: string
  lesson_is_payed: boolean
  lesson_is_future: boolean
}

function subjectName(key: string): string {
  const label = t(key)
  return label === key ? key : label
}

const props = defineProps<{
  show: boolean
  mode: 'add' | 'edit'
  students: StudentOption[]
  subjects: string[]
  topicTree: Record<string, TopicNode[]>
  topicStatuses: Record<number, Record<number, string>>
  defaultPrice: number
  defaultDuration: number
  canAddTopic: boolean
  initialForm?: LessonFormData | null
}>()

const emit = defineEmits<{
  close: []
  submit: [form: LessonFormData]
  delete: []
}>()

const emptyForm = (): LessonFormData => ({
  lesson_id: null,
  lesson_student_id: '',
  lesson_subject: '',
  lesson_topic_id: null,
  lesson_subtopic_id: null,
  lesson_topic_status: 'in_progress',
  lesson_comment: '',
  lesson_price: props.defaultPrice,
  lesson_duration: props.defaultDuration,
  lesson_date: '',
  lesson_time: '',
  lesson_date_payed: '',
  lesson_is_payed: false,
  lesson_is_future: false,
})

const form = ref<LessonFormData>(emptyForm())

const subjectTopics = computed<TopicNode[]>(() => props.topicTree?.[form.value.lesson_subject] ?? [])

const selectedTopic = computed<TopicNode | null>(
  () => subjectTopics.value.find((topic) => topic.id === form.value.lesson_topic_id) ?? null,
)

const subtopics = computed<TopicNode[]>(() => selectedTopic.value?.children ?? [])

const hasTopic = computed(() => form.value.lesson_topic_id !== null)

// ─── Быстрое добавление темы/подтемы ────────────────────────
const showTopicForm = ref(false)
const topicFormParent = ref<{ id: number; name: string } | null>(null)

function openAddTopic(): void {
  if (!props.canAddTopic) {
    toast.warning(t('ui.tariff.limit.topics'))
    return
  }

  topicFormParent.value = null
  showTopicForm.value = true
}

function openAddSubtopic(): void {
  if (!selectedTopic.value) return

  if (!props.canAddTopic) {
    toast.warning(t('ui.tariff.limit.topics'))
    return
  }

  topicFormParent.value = { id: selectedTopic.value.id, name: selectedTopic.value.name }
  showTopicForm.value = true
}

/** После перезагрузки props находим только что созданную тему и выбираем её. */
function selectCreatedTopic(name: string, isSubtopic: boolean): void {
  const roots = props.topicTree?.[form.value.lesson_subject] ?? []

  if (isSubtopic) {
    const parent = roots.find((topic) => topic.id === form.value.lesson_topic_id)
    const created = parent?.children.find((child) => child.name === name)
    if (created) form.value.lesson_subtopic_id = created.id
    return
  }

  const created = roots.find((topic) => topic.name === name)
  if (created) {
    form.value.lesson_topic_id = created.id
    form.value.lesson_subtopic_id = null
  }
}

function submitNewTopic(data: TopicFormData): void {
  const isSubtopic = topicFormParent.value !== null

  router.post('/topics/edit', data, {
    preserveScroll: true,
    onSuccess: () => {
      showTopicForm.value = false
      // Ждём, пока Inertia обновит props с новым деревом тем.
      nextTick(() => selectCreatedTopic(data.name, isSubtopic))
    },
    onError: () => toast.error(t('error.add_topic')),
  })
}

// Статус по умолчанию — «проходим»; для уже усвоенной темы сохраняем «усвоено».
function syncTopicStatus(): void {
  const topicId = form.value.lesson_subtopic_id ?? form.value.lesson_topic_id

  if (topicId === null) {
    form.value.lesson_topic_status = 'in_progress'
    return
  }

  const statuses = props.topicStatuses?.[Number(form.value.lesson_student_id)] ?? {}
  const existing = statuses[topicId]

  form.value.lesson_topic_status = existing === 'mastered' || existing === 'in_progress'
    ? existing
    : 'in_progress'
}

watch(() => form.value.lesson_student_id, syncTopicStatus)
watch(() => form.value.lesson_topic_id, syncTopicStatus)
watch(() => form.value.lesson_subtopic_id, syncTopicStatus)

// Сбрасываем тему/подтему, если они не относятся к выбранному предмету.
watch(() => form.value.lesson_subject, (subject) => {
  const topics = props.topicTree?.[subject] ?? []
  if (form.value.lesson_topic_id !== null && !topics.some((topic) => topic.id === form.value.lesson_topic_id)) {
    form.value.lesson_topic_id = null
    form.value.lesson_subtopic_id = null
  }
})

// Сбрасываем подтему, если она не относится к выбранной теме.
watch(() => form.value.lesson_topic_id, (topicId) => {
  if (form.value.lesson_subtopic_id === null) return
  const topic = subjectTopics.value.find((item) => item.id === topicId)
  if (!topic?.children.some((child) => child.id === form.value.lesson_subtopic_id)) {
    form.value.lesson_subtopic_id = null
  }
})

watch(() => props.initialForm, (val) => {
  if (val) {
    form.value = { ...val }
    syncTopicStatus()
  }
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initialForm) {
    form.value = emptyForm()
  }

  if (!val) {
    showTopicForm.value = false
  }
})

const title = computed(() => props.mode === 'edit' ? t('ui.lessons.form.edit_title') : t('ui.lessons.form.new_title'))
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show && !showTopicForm" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
    </Transition>
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-70 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('close')">
          <Card class="relative w-full max-w-lg p-6 shadow-xl">
          <h2 class="text-2xl font-semibold text-foreground mb-5">
            {{ title }}
          </h2>

          <form @submit.prevent="emit('submit', form)" class="space-y-4">
            <!-- Student -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.student') }}</label>
              <select
                v-model="form.lesson_student_id"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="" disabled>{{ t('ui.lessons.form.student_placeholder') }}</option>
                <option v-for="student in students" :key="student.id" :value="student.id">
                  {{ student.name }}{{ student.current_class ? ` ${student.current_class}` : '' }}
                </option>
              </select>
            </div>

            <!-- Subject -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.subject') }}</label>
              <select
                v-model="form.lesson_subject"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="" disabled>{{ t('ui.lessons.form.subject_placeholder') }}</option>
                <option v-for="subj in subjects" :key="subj" :value="subj">
                  {{ subjectName(subj) }}
                </option>
              </select>
            </div>

            <!-- Topic & Subtopic -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="mb-1.5 flex items-center justify-between gap-2">
                  <label class="block text-sm font-medium text-foreground">{{ t('ui.lessons.form.topic') }}</label>
                  <button
                    type="button"
                    :disabled="!form.lesson_subject"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg h-6 w-6 text-primary hover:bg-primary/10 transition-colors cursor-pointer disabled:cursor-not-allowed disabled:opacity-40"
                    :title="t('ui.lessons.form.add_topic')"
                    @click="openAddTopic"
                  >
                    <Plus class="h-4 w-4" />
                  </button>
                </div>
                <select
                  v-model="form.lesson_topic_id"
                  :disabled="!form.lesson_subject"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors disabled:opacity-50"
                >
                  <option :value="null">{{ t('ui.lessons.form.topic_none') }}</option>
                  <option v-for="topic in subjectTopics" :key="topic.id" :value="topic.id">
                    {{ topic.name }}
                  </option>
                </select>
              </div>
              <div v-if="hasTopic">
                <div class="mb-1.5 flex items-center justify-between gap-2">
                  <label class="block text-sm font-medium text-foreground">{{ t('ui.lessons.form.subtopic') }}</label>
                  <button
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg h-6 w-6 text-primary hover:bg-primary/10 transition-colors cursor-pointer"
                    :title="t('ui.lessons.form.add_subtopic')"
                    @click="openAddSubtopic"
                  >
                    <Plus class="h-4 w-4" />
                  </button>
                </div>
                <select
                  v-model="form.lesson_subtopic_id"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                >
                  <option :value="null">{{ t('ui.lessons.form.subtopic_none') }}</option>
                  <option v-for="sub in subtopics" :key="sub.id" :value="sub.id">
                    {{ sub.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Topic status -->
            <div v-if="hasTopic">
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.topic_status') }}</label>
              <select
                v-model="form.lesson_topic_status"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="in_progress">{{ t('ui.lessons.form.topic_status_in_progress') }}</option>
                <option value="mastered">{{ t('ui.lessons.form.topic_status_mastered') }}</option>
                <option value="review">{{ t('ui.lessons.form.topic_status_review') }}</option>
              </select>
            </div>

            <!-- Comment -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.comment') }}</label>
              <input
                v-model="form.lesson_comment"
                maxlength="255"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                :placeholder="t('ui.lessons.form.comment_placeholder')"
              />
            </div>

            <!-- Date & Time row -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.date') }}</label>
                <input
                  v-model="form.lesson_date"
                  type="date"
                  required
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.time') }}</label>
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
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.price') }}</label>
                <input
                  v-model.number="form.lesson_price"
                  type="number"
                  min="0"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.duration') }}</label>
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
                <span class="text-sm text-foreground">{{ t('ui.lessons.form.is_paid') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.lesson_is_future"
                  type="checkbox"
                  class="rounded border-border text-primary focus:ring-primary/30"
                />
                <span class="text-sm text-foreground">{{ t('ui.lessons.form.is_future') }}</span>
              </label>
            </div>

            <!-- Date payed (when is_payed checked) -->
            <div v-if="form.lesson_is_payed">
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.date_paid') }}</label>
              <input
                v-model="form.lesson_date_payed"
                type="date"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              />
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
              <Button type="submit" class="flex-1">
                {{ mode === 'edit' ? t('ui.common.save') : t('ui.common.add') }}
              </Button>
              <Button
                v-if="mode === 'edit'"
                type="button"
                variant="destructive"
                class="flex-1"
                @click="emit('delete')"
              >
                {{ t('ui.common.delete') }}
              </Button>
              <Button type="button" variant="outline" class="flex-1" @click="emit('close')">
                {{ t('ui.common.cancel') }}
              </Button>
            </div>
          </form>
          </Card>
        </div>
      </div>
    </Transition>
  </Teleport>

  <TopicFormPopup
    :show="showTopicForm"
    mode="add"
    :subject="form.lesson_subject"
    :parent="topicFormParent"
    :initial="null"
    :nested="true"
    @close="showTopicForm = false"
    @submit="submitNewTopic"
  />
</template>