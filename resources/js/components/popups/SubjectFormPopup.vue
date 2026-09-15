<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'
import { useScrollLock } from '@/lib/scrollLock'

export type SubjectFormData = {
  subject_id: number | null
  name: Record<string, string>
  position: number | null
}

const props = defineProps<{
  show: boolean
  initial?: SubjectFormData | null
  submitting?: boolean
}>()

useScrollLock(() => props.show)

const emit = defineEmits<{
  close: []
  submit: [form: SubjectFormData]
}>()

const { t } = useI18n()
const page = usePage<SharedProps>()

const locales = computed(() => page.props.locales ?? [])

const emptyForm = (): SubjectFormData => ({
  subject_id: null,
  name: {},
  position: null,
})

const form = ref<SubjectFormData>(emptyForm())

watch(() => props.initial, (val) => {
  form.value = val
    ? { subject_id: val.subject_id, name: { ...val.name }, position: val.position }
    : emptyForm()
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initial) {
    form.value = emptyForm()
  }
})

const title = computed(() =>
  props.initial?.subject_id ? t('ui.subjects.form.edit_title') : t('ui.subjects.form.add_title'),
)

const isEdit = computed(() => Boolean(props.initial?.subject_id))
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show" class="popup-overlay z-60" @click="emit('close')" />
    </Transition>
    <Transition name="modal">
      <div v-if="show" class="popup-layer z-70">
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('close')">
          <Card class="popup-card max-w-lg">
            <h2 class="popup-title mb-5">{{ title }}</h2>

            <form @submit.prevent="emit('submit', form)" class="space-y-4">
              <div v-for="(locale, index) in locales" :key="locale.code">
                <label class="field-label">
                  {{ t('ui.subjects.form.name') }} — {{ locale.label }}
                </label>
                <input
                  v-model="form.name[locale.code]"
                  maxlength="100"
                  :required="index === 0"
                  class="field w-full px-3.5 py-2.5"
                  :placeholder="t('ui.subjects.form.name_placeholder')"
                />
              </div>

              <div>
                <label class="field-label">{{ t('ui.subjects.form.position') }}</label>
                <input
                  v-model.number="form.position"
                  type="number"
                  min="0"
                  max="100000"
                  step="100"
                  class="field w-full px-3.5 py-2.5"
                  :placeholder="t('ui.subjects.form.position_placeholder')"
                />
                <p class="mt-1 text-xs text-muted-foreground">{{ t('ui.subjects.form.position_hint') }}</p>
              </div>

              <p v-if="!isEdit" class="text-xs text-muted-foreground">{{ t('ui.subjects.form.code_hint') }}</p>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1" :loading="submitting">
                  {{ isEdit ? t('ui.common.save') : t('ui.common.add') }}
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
