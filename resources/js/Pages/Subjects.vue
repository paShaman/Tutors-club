<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { Plus, Pencil, Trash2, RefreshCcw, BookOpen, ListOrdered } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import IconButton from '@/components/ui/IconButton.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import SubjectFormPopup from '@/components/popups/SubjectFormPopup.vue'
import type { SubjectFormData } from '@/components/popups/SubjectFormPopup.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import { cn } from '@/lib/utils'

defineOptions({ layout: AppLayout })

interface SubjectRow {
  id: number
  code: string
  slug: string
  name: Record<string, string>
  position: number
  is_deleted: boolean
  lessons_count: number
  topics_count: number
}

const page = usePage<{ subjects: SubjectRow[] }>()
const { t, locale } = useI18n()
const toast = useToast()

const subjects = computed(() => page.props.subjects ?? [])
const showDeleted = ref(false)

const visibleSubjects = computed(() =>
  subjects.value.filter((subject) => showDeleted.value ? subject.is_deleted : !subject.is_deleted),
)

const showForm = ref(false)
const editing = ref<SubjectFormData | null>(null)
const submitting = ref(false)

const showConfirm = ref(false)
const deleting = ref<SubjectRow | null>(null)
const restoring = ref(false)

function displayName(subject: SubjectRow): string {
  const names = subject.name ?? {}
  return names[locale.value] || names['ru'] || Object.values(names).find(Boolean) || subject.code
}

function openAdd() {
  editing.value = null
  showForm.value = true
}

function openEdit(subject: SubjectRow) {
  editing.value = {
    subject_id: subject.id,
    name: { ...subject.name },
    position: subject.position,
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editing.value = null
}

function submit(data: SubjectFormData) {
  if (submitting.value) return

  const isEdit = data.subject_id !== null
  submitting.value = true
  router.post('/admin/subjects/edit', data, {
    preserveScroll: true,
    onSuccess: () => closeForm(),
    onError: () => toast.error(t(isEdit ? 'error.edit_subject' : 'error.add_subject')),
    onFinish: () => { submitting.value = false },
  })
}

function askDelete(subject: SubjectRow) {
  deleting.value = subject
  restoring.value = false
  showConfirm.value = true
}

function askRestore(subject: SubjectRow) {
  deleting.value = subject
  restoring.value = true
  showConfirm.value = true
}

function confirmAction() {
  const subject = deleting.value
  if (!subject) return

  showConfirm.value = false
  const url = restoring.value ? '/admin/subjects/restore' : '/admin/subjects/delete'
  const errorKey = restoring.value ? 'error.restore_subject' : 'error.delete_subject'

  router.post(url, { subject_id: subject.id }, {
    preserveScroll: true,
    onSuccess: () => { deleting.value = null },
    onError: () => toast.error(t(errorKey)),
  })
}

function cancelAction() {
  showConfirm.value = false
  deleting.value = null
}

const confirmMessage = computed(() => {
  const subject = deleting.value
  if (!subject || restoring.value) return t('ui.subjects.restore_confirm')

  const total = subject.lessons_count + subject.topics_count
  if (total > 0) {
    return t('ui.subjects.delete_used_confirm', {
      lessons: subject.lessons_count,
      topics: subject.topics_count,
    })
  }

  return t('ui.subjects.delete_confirm')
})
</script>

<template>
  <Head :title="t('ui.subjects.title')" />

  <div class="max-w-4xl space-y-6 animate-fade-up">
    <div class="page-header">
      <div>
        <h1 class="page-title text-3xl">{{ t('ui.subjects.title') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ t('ui.subjects.subtitle') }}</p>
      </div>
      <div class="page-header-actions">
        <Button
          variant="outline"
          :title="showDeleted ? t('ui.subjects.active') : t('ui.subjects.deleted')"
          @click="showDeleted = !showDeleted"
        >
          <BookOpen v-if="showDeleted" class="h-4 w-4" />
          <Trash2 v-else class="h-4 w-4" />
          <span class="hidden sm:inline">{{ showDeleted ? t('ui.subjects.active') : t('ui.subjects.deleted') }}</span>
        </Button>
        <Button :title="t('ui.subjects.add')" @click="openAdd">
          <Plus />
          <span class="hidden sm:inline">{{ t('ui.subjects.add') }}</span>
        </Button>
      </div>
    </div>

    <div v-if="visibleSubjects.length" class="space-y-3">
      <Card
        v-for="subject in visibleSubjects"
        :key="subject.id"
        :class="cn('p-5', subject.is_deleted && 'opacity-60 grayscale')"
      >
        <div class="flex items-start gap-4">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <BookOpen class="h-5 w-5" />
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-semibold text-foreground">{{ displayName(subject) }}</p>
              <span class="pill bg-muted px-2.5 py-0.5 font-mono text-xs text-muted-foreground">
                {{ subject.code }}
              </span>
            </div>

            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
              <span class="inline-flex items-center gap-1.5">
                <ListOrdered class="h-3.5 w-3.5" />
                {{ t('ui.subjects.position_label', { position: subject.position }) }}
              </span>
              <span>{{ t('ui.subjects.lessons_count', { count: subject.lessons_count }) }}</span>
              <span>{{ t('ui.subjects.topics_count', { count: subject.topics_count }) }}</span>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <IconButton
              v-if="!subject.is_deleted"
              :title="t('ui.common.edit')"
              @click="openEdit(subject)"
            >
              <Pencil />
            </IconButton>
            <IconButton
              v-if="subject.is_deleted"
              variant="success"
              :title="t('ui.common.restore')"
              @click="askRestore(subject)"
            >
              <RefreshCcw />
            </IconButton>
            <IconButton
              v-else
              variant="destructive"
              :title="t('ui.common.delete')"
              @click="askDelete(subject)"
            >
              <Trash2 />
            </IconButton>
          </div>
        </div>
      </Card>
    </div>

    <Card v-else class="p-10 text-center">
      <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
        <BookOpen class="h-6 w-6 text-muted-foreground" />
      </div>
      <p class="font-medium text-foreground">
        {{ showDeleted ? t('ui.subjects.empty_deleted') : t('ui.subjects.empty') }}
      </p>
      <p v-if="!showDeleted" class="mt-1 text-sm text-muted-foreground">{{ t('ui.subjects.empty_hint') }}</p>
    </Card>
  </div>

  <SubjectFormPopup
    :show="showForm"
    :initial="editing"
    :submitting="submitting"
    @close="closeForm"
    @submit="submit"
  />

  <ConfirmDialog
    :show="showConfirm"
    :title="restoring ? t('ui.common.restore') : t('ui.common.delete')"
    :message="confirmMessage"
    :confirm-text="restoring ? t('ui.common.restore') : t('ui.common.delete')"
    :variant="restoring ? 'default' : 'danger'"
    @confirm="confirmAction"
    @cancel="cancelAction"
  />
</template>
