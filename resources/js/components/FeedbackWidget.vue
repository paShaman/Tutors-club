<script setup lang="ts">
import { watch } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { Send } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import type { SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

type FeedbackFormData = {
  contact: string
  message: string
}

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const page = usePage<SharedProps>()
const { t } = useI18n()

const form = useForm<FeedbackFormData>({
  contact: '',
  message: '',
})

function defaultContact(): string {
  return page.props.auth?.user?.email ?? ''
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      form.clearErrors()
      form.contact = defaultContact()
      form.message = ''
    }
  },
)

function submit(): void {
  form.post('/feedback', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('close')
      form.reset()
    },
  })
}
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
              {{ t('ui.feedback.title') }}
            </h2>
            <p class="mb-5 text-sm text-muted-foreground">{{ t('ui.feedback.hint') }}</p>

            <form class="space-y-4" @submit.prevent="submit">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.feedback.contact') }}
                </label>
                <input
                  v-model="form.contact"
                  type="text"
                  maxlength="255"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :placeholder="t('ui.feedback.contact_placeholder')"
                />
                <p v-if="form.errors.contact" class="mt-1 text-xs text-destructive">{{ form.errors.contact }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">
                  {{ t('ui.feedback.message') }}
                </label>
                <textarea
                  v-model="form.message"
                  rows="4"
                  maxlength="5000"
                  class="w-full rounded-xl border border-border bg-white/50 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                  :placeholder="t('ui.feedback.message_placeholder')"
                />
                <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1" :disabled="form.processing">
                  <Send class="h-4 w-4" />
                  {{ t('ui.feedback.submit') }}
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
