<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import AvatarPicker from '@/components/ui/AvatarPicker.vue'

export type StudentFormData = {
  student_id: number | null
  student_name: string
  student_class: string
  student_type: string
  student_description: string
  student_avatar: string
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
  student_class: '',
  student_type: '',
  student_description: '',
  student_avatar: '',
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

const title = computed(() => props.mode === 'edit' ? 'Редактировать ученика' : 'Новый ученик')
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
              <div v-if="form.student_id" class="border-b border-border/60 pb-4">
                <p class="block text-sm font-medium text-foreground mb-3">Фотография</p>
                <AvatarPicker
                  v-model="form.student_avatar"
                  :name="form.student_name"
                  :show-remove="true"
                />
              </div>

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
                  {{ mode === 'edit' ? 'Сохранить' : 'Добавить' }}
                </Button>
                <Button type="button" variant="outline" class="flex-1" @click="emit('close')">
                  Отмена
                </Button>
              </div>
            </form>
          </Card>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>