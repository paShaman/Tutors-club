<script setup lang="ts">
import { ref, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'

export type ReviewFormData = {
  reviewed_on: string
  comment: string
}

const props = defineProps<{
  show: boolean
  topicName: string
}>()

const emit = defineEmits<{
  close: []
  submit: [form: ReviewFormData]
}>()

const { t } = useI18n()

function today(): string {
  const d = new Date()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${month}-${day}`
}

const emptyForm = (): ReviewFormData => ({
  reviewed_on: today(),
  comment: '',
})

const form = ref<ReviewFormData>(emptyForm())

watch(() => props.show, (val) => {
  if (val) {
    form.value = emptyForm()
  }
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
            <h2 class="text-2xl font-semibold text-foreground mb-1">
              {{ t('ui.planning.review_form.title') }}
            </h2>
            <p class="mb-5 text-sm text-muted-foreground">{{ topicName }}</p>

            <form @submit.prevent="emit('submit', form)" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.planning.review_form.date') }}
                </label>
                <input
                  v-model="form.reviewed_on"
                  type="date"
                  required
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.planning.review_form.comment') }}
                </label>
                <textarea
                  v-model="form.comment"
                  rows="3"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                  :placeholder="t('ui.planning.review_form.comment_placeholder')"
                />
              </div>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1">
                  {{ t('ui.common.save') }}
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
