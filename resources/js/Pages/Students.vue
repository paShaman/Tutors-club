<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { ref, computed } from 'vue'
import { cn } from '@/lib/utils'
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
  }>
  deletedFlag: boolean
  specialFlag: boolean
  flash: { success: string | null; error: string | null }
}>()

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

const form = ref({
  student_id: null as number | null,
  student_name: '',
  student_class: '',
  student_type: '',
  student_description: '',
})

function openAddModal() {
  form.value = { student_id: null, student_name: '', student_class: '', student_type: '', student_description: '' }
  showAddModal.value = true
}

function openEditModal(student: any) {
  form.value = {
    student_id: student.id,
    student_name: student.name,
    student_class: student.class ?? '',
    student_type: student.type ?? '',
    student_description: student.description ?? '',
  }
  editingStudent.value = student
  showEditModal.value = true
}

function closeModals() {
  showAddModal.value = false
  showEditModal.value = false
  editingStudent.value = null
}

async function submitStudent() {
  try {
    const response = await fetch('/students/edit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(form.value),
    })
    const data = await response.json()

    if (data.success) {
      closeModals()
      window.location.reload()
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}

async function deleteStudent(student: any) {
  if (!confirm(student.is_deleted ? 'Восстановить ученика?' : 'Удалить ученика?')) return

  try {
    const response = await fetch('/students/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ student_id: student.id, is_deleted: student.is_deleted ? 0 : 1 }),
    })
    const data = await response.json()

    if (data.success) {
      window.location.reload()
    } else {
      alert(typeof data.data === 'string' ? data.data : Object.values(data.data).join('\n'))
    }
  } catch (e) {
    alert('Ошибка сети')
  }
}
</script>

<template>
  <Head title="Ученики" />

  <div class="space-y-6 animate-fade-up">
    <!-- Page header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10">
          <GraduationCap class="h-6 w-6 text-emerald-600" />
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-foreground">
            Ученики
          </h1>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ filteredStudents.length }} {{ filteredStudents.length === 1 ? 'ученик' : (filteredStudents.length < 5 ? 'ученика' : 'учеников') }}
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
          {{ showDeleted ? 'Активные' : 'Удалённые' }}
        </Button>
        <Button @click="openAddModal">
          <UserPlus class="h-4 w-4" />
          Добавить ученика
        </Button>
      </div>
    </div>

    <!-- Flash message -->
    <div
      v-if="page.props.flash?.success"
      class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 border border-emerald-200"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.flash?.error"
      class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 border border-red-200"
    >
      {{ page.props.flash.error }}
    </div>

    <!-- Students Grid -->
    <template v-if="filteredStudents.length">
      <!-- Regular students -->
      <template v-if="regularStudents.length">
        <div class="flex items-center gap-2 px-1">
          <GraduationCap class="h-4 w-4 text-blue-500" />
          <span class="text-sm font-semibold text-blue-600">Обычные ученики</span>
          <span class="text-xs text-muted-foreground">· {{ regularStudents.length }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="student in regularStudents"
            :key="student.id"
            :class="cn(
              'p-5 transition-all duration-200 hover:shadow-md',
              student.is_deleted && 'opacity-60 grayscale',
            )"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-semibold text-white shadow shrink-0 bg-gradient-to-br from-blue-500 to-indigo-500">
                  {{ student.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="font-medium text-foreground">{{ student.name }}</h3>
                  <p class="text-xs text-muted-foreground">
                    {{ student.current_class || 'Класс не указан' }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button
                  v-if="!student.is_deleted"
                  @click="openEditModal(student)"
                  class="inline-flex items-center justify-center rounded-lg h-9 w-9 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                  title="Редактировать"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  @click="deleteStudent(student)"
                  :class="cn(
                    'inline-flex items-center justify-center rounded-lg h-9 w-9 transition-colors',
                    student.is_deleted
                      ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 cursor-pointer'
                      : 'text-muted-foreground hover:bg-red-50 hover:text-red-600 cursor-pointer',
                  )"
                  :title="student.is_deleted ? 'Восстановить' : 'Удалить'"
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
          <span class="text-sm font-semibold text-amber-600">Особая группа</span>
          <span class="text-xs text-muted-foreground">· {{ specialStudents.length }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="student in specialStudents"
            :key="student.id"
            :class="cn(
              'p-5 transition-all duration-200 hover:shadow-md border-amber-200/50 bg-amber-50/30',
              student.is_deleted && 'opacity-60 grayscale',
            )"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-full text-sm font-semibold text-white shadow shrink-0 bg-gradient-to-br from-amber-500 to-orange-500">
                  {{ student.name.charAt(0).toUpperCase() }}
                </div>
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
                  @click="openEditModal(student)"
                  class="inline-flex items-center justify-center rounded-lg h-9 w-9 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                  title="Редактировать"
                >
                  <Pencil class="h-4 w-4" />
                </button>
                <button
                  @click="deleteStudent(student)"
                  :class="cn(
                    'inline-flex items-center justify-center rounded-lg h-9 w-9 transition-colors',
                    student.is_deleted
                      ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 cursor-pointer'
                      : 'text-muted-foreground hover:bg-red-50 hover:text-red-600 cursor-pointer',
                  )"
                  :title="student.is_deleted ? 'Восстановить' : 'Удалить'"
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
        {{ showDeleted ? 'Нет удалённых учеников' : 'Пока нет учеников' }}
      </p>
      <Button v-if="!showDeleted" @click="openAddModal" variant="outline" class="mt-4">
        <UserPlus class="h-4 w-4" />
        Добавить первого ученика
      </Button>
    </Card>

    <!-- Add/Edit Modal Overlay -->
    <Teleport to="body">
      <Transition name="overlay">
        <div v-if="showModal" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="closeModals" />
      </Transition>
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-70 flex items-center justify-center p-4 overflow-y-auto">
          <Card class="relative w-full max-w-md p-6 shadow-xl">
          <h2 class="text-2xl font-semibold text-foreground mb-5">
            {{ showEditModal ? 'Редактировать ученика' : 'Новый ученик' }}
          </h2>

          <form @submit.prevent="submitStudent" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Имя *</label>
              <input
                v-model="form.student_name"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                placeholder="Имя ученика"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Класс</label>
              <input
                v-model="form.student_class"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                placeholder="Например: 9Б"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Тип</label>
              <input
                v-model="form.student_type"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                placeholder="Например: IELTS, ОГЭ, ЕГЭ"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Описание</label>
              <textarea
                v-model="form.student_description"
                rows="3"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                placeholder="Заметки об ученике"
              />
            </div>

            <div class="flex items-center gap-3 pt-2">
              <Button type="submit" class="flex-1">
                {{ showEditModal ? 'Сохранить' : 'Добавить' }}
              </Button>
              <Button type="button" variant="outline" class="flex-1" @click="closeModals">
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