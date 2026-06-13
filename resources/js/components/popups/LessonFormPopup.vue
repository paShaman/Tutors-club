<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'

export interface StudentOption {
  id: number
  name: string
  current_class: string
}

export interface LessonFormData {
  lesson_id: number | null
  lesson_student_id: string
  lesson_subject: string
  lesson_theme: string
  lesson_price: number
  lesson_duration: number
  lesson_date: string
  lesson_time: string
  lesson_date_payed: string
  lesson_is_payed: boolean
  lesson_is_future: boolean
}

const subjectLabels: Record<string, string> = {
  lesson_subject_maths: 'Математика',
  lesson_subject_informatics: 'Информатика',
  lesson_subject_english: 'Английский',
}

function subjectName(key: string): string {
  return subjectLabels[key] ?? key
}

const props = withDefaults(defineProps<{
  show: boolean
  mode: 'add' | 'edit'
  students: StudentOption[]
  subjects: string[]
  defaultPrice?: number
  defaultDuration?: number
  initialForm?: LessonFormData | null
}>(), {
  defaultPrice: 3000,
  defaultDuration: 60,
})

const emit = defineEmits<{
  close: []
  submit: [form: LessonFormData]
  delete: []
}>()

const emptyForm = (): LessonFormData => ({
  lesson_id: null,
  lesson_student_id: '',
  lesson_subject: '',
  lesson_theme: '',
  lesson_price: props.defaultPrice,
  lesson_duration: props.defaultDuration,
  lesson_date: '',
  lesson_time: '',
  lesson_date_payed: '',
  lesson_is_payed: false,
  lesson_is_future: false,
})

const form = ref<LessonFormData>(emptyForm())

watch(() => props.initialForm, (val) => {
  if (val) {
    form.value = { ...val }
  }
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initialForm) {
    form.value = emptyForm()
  }
})

const title = computed(() => props.mode === 'edit' ? 'Редактировать урок' : 'Новый урок')
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
    </Transition>
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-70 flex items-center justify-center p-4 overflow-y-auto">
        <Card class="relative w-full max-w-lg p-6 shadow-xl">
          <h2 class="text-2xl font-semibold text-foreground mb-5">
            {{ title }}
          </h2>

          <form @submit.prevent="emit('submit', form)" class="space-y-4">
            <!-- Student -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">Ученик *</label>
              <select
                v-model="form.lesson_student_id"
                required
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
              >
                <option value="" disabled>Выберите ученика</option>
                <option v-for="student in students" :key="student.id" :value="student.id">
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
                <option v-for="subj in subjects" :key="subj" :value="subj">
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
                {{ mode === 'edit' ? 'Сохранить' : 'Добавить' }}
              </Button>
              <Button
                v-if="mode === 'edit'"
                type="button"
                variant="destructive"
                class="flex-1"
                @click="emit('delete')"
              >
                Удалить
              </Button>
              <Button type="button" variant="outline" class="flex-1" @click="emit('close')">
                Отмена
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </Transition>
  </Teleport>
</template>