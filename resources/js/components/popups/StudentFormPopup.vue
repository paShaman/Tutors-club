<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import StudentAvatar from '@/components/ui/StudentAvatar.vue'
import StudentColorPicker from '@/components/ui/StudentColorPicker.vue'
import { randomStudentColor } from '@/lib/studentColors'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

export type StudentFormData = {
  student_id: number | null
  student_name: string
  student_gender: string
  student_color: string
  student_class: string
  student_type: string
  student_description: string
}

const props = withDefaults(defineProps<{
  show: boolean
  mode: 'add' | 'edit'
  initialForm?: StudentFormData | null
}>(), {})

const emit = defineEmits<{
  close: []
  submit: [form: StudentFormData]
}>()

const emptyForm = (): StudentFormData => ({
  student_id: null,
  student_name: '',
  student_gender: 'boy',
  student_color: randomStudentColor(),
  student_class: '',
  student_type: '',
  student_description: '',
})

const form = ref<StudentFormData>(emptyForm())

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

const title = computed(() => props.mode === 'edit' ? t('ui.students.form.edit_title') : t('ui.students.form.new_title'))
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show" class="fixed inset-0 z-60 bg-black/40 backdrop-blur-sm" @click="emit('close')" />
    </Transition>
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-70 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('close')">
          <Card class="relative w-full max-w-md p-6 shadow-xl">
            <h2 class="text-2xl font-semibold text-foreground mb-5">
              {{ title }}
            </h2>

            <form @submit.prevent="emit('submit', form)" class="space-y-4">
              <div class="flex items-start gap-4 border-b border-border/60 pb-4">
                <StudentAvatar
                  :name="form.student_name"
                  :gender="form.student_gender"
                  :color="form.student_color"
                  class="h-16 w-16"
                />
                <div class="flex-1 space-y-2">
                  <p class="text-sm font-medium text-foreground">{{ t('ui.students.form.color') }}</p>
                  <StudentColorPicker v-model="form.student_color" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.students.form.gender') }}</label>
                <div class="flex gap-2">
                  <Button
                    type="button"
                    :variant="form.student_gender === 'boy' ? 'default' : 'outline'"
                    class="flex-1"
                    @click="form.student_gender = 'boy'"
                  >
                    {{ t('ui.students.form.boy') }}
                  </Button>
                  <Button
                    type="button"
                    :variant="form.student_gender === 'girl' ? 'default' : 'outline'"
                    class="flex-1"
                    @click="form.student_gender = 'girl'"
                  >
                    {{ t('ui.students.form.girl') }}
                  </Button>
                  <Button
                    type="button"
                    :variant="form.student_gender === 'none' ? 'default' : 'outline'"
                    class="flex-1"
                    @click="form.student_gender = 'none'"
                  >
                    {{ t('ui.students.form.none') }}
                  </Button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.students.form.name') }}</label>
                <input
                  v-model="form.student_name"
                  required
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :placeholder="t('ui.students.form.name_placeholder')"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.students.form.class') }}</label>
                <input
                  v-model="form.student_class"
                  required
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :placeholder="t('ui.students.form.class_placeholder')"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.students.form.type') }}</label>
                <input
                  v-model="form.student_type"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :placeholder="t('ui.students.form.type_placeholder')"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ t('ui.students.form.description') }}</label>
                <textarea
                  v-model="form.student_description"
                  rows="3"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                  :placeholder="t('ui.students.form.description_placeholder')"
                />
              </div>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1">
                  {{ mode === 'edit' ? t('ui.common.save') : t('ui.common.add') }}
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
