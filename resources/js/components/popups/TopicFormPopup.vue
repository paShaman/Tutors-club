<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'

export type TopicFormData = {
  topic_id: number | null
  subject: string
  parent_id: number | null
  name: string
}

const props = defineProps<{
  show: boolean
  mode: 'add' | 'edit'
  subject: string
  parent?: { id: number; name: string } | null
  initial?: TopicFormData | null
}>()

const emit = defineEmits<{
  close: []
  submit: [form: TopicFormData]
}>()

const { t } = useI18n()

const emptyForm = (): TopicFormData => ({
  topic_id: null,
  subject: props.subject,
  parent_id: props.parent?.id ?? null,
  name: '',
})

const form = ref<TopicFormData>(emptyForm())

watch(() => props.initial, (val) => {
  if (val) {
    form.value = { ...val }
  }
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initial) {
    form.value = emptyForm()
  }
})

const title = computed(() => {
  if (props.mode === 'edit') {
    return t('ui.planning.form.edit_title')
  }
  return props.parent
    ? t('ui.planning.form.new_subtopic_title')
    : t('ui.planning.form.new_topic_title')
})
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
              <div v-if="parent && mode === 'add'">
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.planning.form.parent') }}
                </label>
                <div class="rounded-xl border border-border bg-muted/40 px-3.5 py-2.5 text-sm text-muted-foreground">
                  {{ parent.name }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.planning.form.name') }}
                </label>
                <input
                  v-model="form.name"
                  required
                  autofocus
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :placeholder="t('ui.planning.form.name_placeholder')"
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
