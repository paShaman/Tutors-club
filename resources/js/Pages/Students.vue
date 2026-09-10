<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { ref, computed } from 'vue'
import { cn } from '@/lib/utils'
import StudentFormPopup from '@/components/popups/StudentFormPopup.vue'
import type { StudentFormData } from '@/components/popups/StudentFormPopup.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import ConfirmDialog from '@/components/popups/ConfirmDialog.vue'
import { useToast } from '@/lib/toast'
import { useI18n } from '@/lib/i18n'
import {
  UserPlus,
  GraduationCap,
  Pencil,
  Trash2,
  RefreshCcw,
  Star,
} from 'lucide-vue-next'

defineOptions({ layout: AppLayout })

const page = usePage<{
  students: Array<{
    id: number
    name: string
    class: string | null
    current_class: string
    type: string | null
    description: string | null
    is_deleted: number
    avatar: string | null
  }>
  deletedFlag: boolean
  specialFlag: boolean
}>()

const toast = useToast()
const { t, tp } = useI18n()

const students = computed(() => page.props.students ?? [])
const deletedFlag = computed(() => page.props.deletedFlag ?? false)
const specialFlag = computed(() => page.props.specialFlag ?? false)

const showDeleted = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingStudent = ref<any>(null)

const showModal = computed(() => showAddModal.value || showEditModal.value)

const filteredStudents = computed(() => {
  return students.value.filter((s: any) => showDeleted.value ? !!s.is_deleted : !s.is_deleted)
})

const specialStudents = computed(() => filteredStudents.value.filter((s: any) => !!s.type))
const regularStudents = computed(() => filteredStudents.value.filter((s: any) => !s.type))

const initialForm = ref<StudentFormData | null>(null)

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

function openAddModal() {
  initialForm.value = null
  showAddModal.value = true
}

function openStudent(student: any) {
  router.get(`/students/${student.id}`)
}

function openEditModal(student: any) {
  initialForm.value = {
    student_id: student.id,
    student_name: student.name,
    student_class: student.class ?? '',
    student_type: student.type ?? '',
    student_description: student.description ?? '',
    student_avatar: student.avatar ?? '',
  }
  editingStudent.value = student
  showEditModal.value = true
}

function closeModals() {
  showAddModal.value = false
  showEditModal.value = false
  editingStudent.value = null
}

function submitStudent(formData: StudentFormData) {
  router.post('/students/edit', formData, {
    preserveScroll: true,
    onSuccess: () => closeModals(),
    onError: (errors) => toast.error(Object.values(errors).join('\n')),
  })
}

function deleteStudent(student: any) {
  openConfirm(
    student.is_deleted ? t('ui.students.restore_confirm') : t('ui.students.delete_confirm'),
    'danger',
    () => {
      router.post('/students/delete', {
        student_id: student.id,
        is_deleted: student.is_deleted ? 0 : 1,
      }, {
        preserveScroll: true,
        onError: (errors) => toast.error(Object.values(errors).join('\n')),
      })
    },
  )
}
</script>

<template>
  <Head :title="t('ui.students.title')" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10">
          <GraduationCap class="h-6 w-6 text-emerald-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            {{ t('ui.students.title') }}
          </h1>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ tp('ui.students.count', filteredStudents.length) }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <Button
          v-if="deletedFlag"
          @click="showDeleted = !showDeleted"
          :variant="showDeleted ? 'destructive' : 'outline'"
        >
          <Trash2 class="h-4 w-4" />
          {{ showDeleted ? t('ui.students.active') : t('ui.students.deleted') }}
        </Button>
        <Button @click="openAddModal">
          <UserPlus class="h-4 w-4" />
          {{ t('ui.students.add') }}
        </Button>
      </div>
    </div>

    <!-- Students Grid -->
    <template v-if="filteredStudents.length">
      <!-- Regular students -->
      <template v-if="regularStudents.length">
        <div class="flex items-center gap-2 px-1">
          <GraduationCap class="h-4 w-4 text-blue-500" />
          <span class="text-sm font-semibold text-blue-600">{{ t('ui.students.regular') }}</span>
          <span class="text-xs text-muted-foreground">· {{ regularStudents.length }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="student in regularStudents"
            :key="student.id"
            :class="cn(
              'p-5 transition-all duration-200 hover:shadow-md cursor-pointer',
              student.is_deleted && 'opacity-60 grayscale',
            )"
            @click="openStudent(student)"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <UserAvatar
                  :name="student.name"
                  :src="student.avatar"
                  class="h-11 w-11 bg-gradient-to-br from-blue-500 to-indigo-500 text-sm font-semibold text-white shadow"
                />
                <div>
                  <h3 class="font-medium text-foreground">{{ student.name }}</h3>
                  <p class="text-xs text-muted-foreground">
                    {{ student.current_class || t('ui.students.class_not_set') }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button
                  v-if="!student.is_deleted"
                  @click.stop="openEditModal(student)"
                  class="inline-flex items-center justify-center rounded-lg h-9 w-9 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                  :title="t('ui.common.edit')"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  @click.stop="deleteStudent(student)"
                  :class="cn(
                    'inline-flex items-center justify-center rounded-lg h-9 w-9 transition-colors',
                    student.is_deleted
                      ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 cursor-pointer'
                      : 'text-muted-foreground hover:bg-red-50 hover:text-red-600 cursor-pointer',
                  )"
                  :title="student.is_deleted ? t('ui.common.restore') : t('ui.common.delete')"
                >
                  <RefreshCcw v-if="student.is_deleted" class="h-4 w-4" />
                  <Trash2 v-else class="h-4 w-4" />
                </button>
              </div>
            </div>
            <p v-if="student.description" class="mt-3 text-sm text-muted-foreground line-clamp-2">
              {{ student.description }}
            </p>
          </Card>
        </div>
      </template>

      <!-- Special students group -->
      <template v-if="specialStudents.length">
        <div :class="cn('flex items-center gap-2 px-1', regularStudents.length && 'mt-6')">
          <Star class="h-4 w-4 text-amber-500" />
          <span class="text-sm font-semibold text-amber-600">{{ t('ui.students.special') }}</span>
          <span class="text-xs text-muted-foreground">· {{ specialStudents.length }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="student in specialStudents"
            :key="student.id"
            :class="cn(
              'p-5 transition-all duration-200 hover:shadow-md border-amber-200/50 bg-amber-50/30 cursor-pointer',
              student.is_deleted && 'opacity-60 grayscale',
            )"
            @click="openStudent(student)"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <UserAvatar
                  :name="student.name"
                  :src="student.avatar"
                  class="h-11 w-11 bg-gradient-to-br from-amber-500 to-orange-500 text-sm font-semibold text-white shadow"
                />
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="font-medium text-foreground">{{ student.name }}</h3>
                    <Star class="h-3.5 w-3.5 text-amber-500" />
                  </div>
                  <p class="text-xs font-medium text-amber-600">
                    {{ student.type }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button
                  v-if="!student.is_deleted"
                  @click.stop="openEditModal(student)"
                  class="inline-flex items-center justify-center rounded-lg h-9 w-9 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                  :title="t('ui.common.edit')"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  @click.stop="deleteStudent(student)"
                  :class="cn(
                    'inline-flex items-center justify-center rounded-lg h-9 w-9 transition-colors',
                    student.is_deleted
                      ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 cursor-pointer'
                      : 'text-muted-foreground hover:bg-red-50 hover:text-red-600 cursor-pointer',
                  )"
                  :title="student.is_deleted ? t('ui.common.restore') : t('ui.common.delete')"
                >
                  <RefreshCcw v-if="student.is_deleted" class="h-4 w-4" />
                  <Trash2 v-else class="h-4 w-4" />
                </button>
              </div>
            </div>
            <p v-if="student.description" class="mt-3 text-sm text-muted-foreground line-clamp-2">
              {{ student.description }}
            </p>
          </Card>
        </div>
      </template>
    </template>

    <!-- Empty state -->
    <Card v-else class="p-12 text-center">
      <GraduationCap class="mx-auto h-12 w-12 text-muted-foreground/30" />
      <p class="mt-4 text-muted-foreground">
        {{ showDeleted ? t('ui.students.empty_deleted') : t('ui.students.empty') }}
      </p>
      <Button v-if="!showDeleted" @click="openAddModal" variant="outline" class="mt-4">
        <UserPlus class="h-4 w-4" />
        {{ t('ui.students.add_first') }}
      </Button>
    </Card>

    <StudentFormPopup
      :show="showModal"
      :mode="showEditModal ? 'edit' : 'add'"
      :initial-form="initialForm"
      @close="closeModals"
      @submit="submitStudent"
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
