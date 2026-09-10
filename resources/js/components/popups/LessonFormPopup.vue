<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

export interface StudentOption {
  id: number
  name: string
  current_class: string
}

export type LessonFormData = {
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

function subjectName(key: string): string {
  const label = t(key)
  return label === key ? key : label
}

const props = defineProps<{
  show: boolean
  mode: 'add' | 'edit'
  students: StudentOption[]
  subjects: string[]
  defaultPrice: number
  defaultDuration: number
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

const title = computed(() => props.mode === 'edit' ? t('ui.lessons.form.edit_title') : t('ui.lessons.form.new_title'))
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
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

            <!-- Theme -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.lessons.form.theme') }}</label>
              <input
                v-model="form.lesson_theme"
                class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                :placeholder="t('ui.lessons.form.theme_placeholder')"
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
</template>