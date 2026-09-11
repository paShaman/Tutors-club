<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3'
import { ImagePlus, Send, X } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import type { SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'
import { useToast } from '@/lib/toast'

type FeedbackFormData = {
  contact: string
  message: string
  photos: File[]
}

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const page = usePage<SharedProps>()
const { t } = useI18n()
const toast = useToast()

const MAX_PHOTOS = 3
const MAX_PHOTO_SIZE = 10 * 1024 * 1024

const form = useForm<FeedbackFormData>({
  contact: '',
  message: '',
  photos: [],
})

const photoInput = ref<HTMLInputElement | null>(null)
const photoUrls = ref<string[]>([])

const photoError = computed<string>(() => {
  const errors = form.errors as Record<string, string | undefined>
  return errors.photos ?? errors['photos.0'] ?? ''
})

function defaultContact(): string {
  return page.props.auth?.user?.email ?? ''
}

function addPhotos(files: File[]): void {
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue

    if (form.photos.length >= MAX_PHOTOS) {
      toast.warning(t('ui.feedback.photos_limit'))
      break
    }
    if (file.size > MAX_PHOTO_SIZE) {
      toast.warning(t('ui.feedback.photo_too_large'))
      continue
    }

    form.photos.push(file)
    photoUrls.value.push(URL.createObjectURL(file))
  }
}

function onFilesChange(e: Event): void {
  const input = e.target as HTMLInputElement
  addPhotos(Array.from(input.files ?? []))
  input.value = ''
}

function removePhoto(index: number): void {
  URL.revokeObjectURL(photoUrls.value[index])
  photoUrls.value.splice(index, 1)
  form.photos.splice(index, 1)
}

function onPaste(e: ClipboardEvent): void {
  const items = e.clipboardData?.items
  if (!items) return

  const files: File[] = []
  for (const item of Array.from(items)) {
    if (item.kind === 'file' && item.type.startsWith('image/')) {
      const file = item.getAsFile()
      if (file) files.push(file)
    }
  }

  if (files.length > 0) {
    e.preventDefault()
    addPhotos(files)
  }
}

function resetPhotos(): void {
  photoUrls.value.forEach((url) => URL.revokeObjectURL(url))
  photoUrls.value = []
  form.photos = []
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      form.clearErrors()
      form.contact = defaultContact()
      form.message = ''
    }
    resetPhotos()
  },
)

function submit(): void {
  form.post('/feedback', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('close')
      form.reset()
      resetPhotos()
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
                  @paste="onPaste"
                />
                <p v-if="form.errors.message" class="mt-1 text-xs text-destructive">{{ form.errors.message }}</p>
              </div>

              <div>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition-all hover:border-foreground/20 hover:shadow-md disabled:opacity-50"
                  :disabled="form.photos.length >= MAX_PHOTOS"
                  @click="photoInput?.click()"
                >
                  <ImagePlus class="h-3.5 w-3.5" />
                  {{ t('ui.feedback.attach') }}
                </button>
                <p class="mt-1.5 text-xs text-muted-foreground">{{ t('ui.feedback.attach_hint') }}</p>

                <div v-if="photoUrls.length" class="mt-2 flex flex-wrap gap-2">
                  <div
                    v-for="(url, index) in photoUrls"
                    :key="url"
                    class="group relative h-16 w-16 overflow-hidden rounded-lg border border-border"
                  >
                    <img :src="url" alt="" class="h-full w-full object-cover" />
                    <button
                      type="button"
                      class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition-opacity group-hover:opacity-100"
                      :title="t('ui.feedback.remove_photo')"
                      @click="removePhoto(index)"
                    >
                      <X class="h-3 w-3" />
                    </button>
                  </div>
                </div>

                <p v-if="photoError" class="mt-1 text-xs text-destructive">{{ photoError }}</p>

                <input
                  ref="photoInput"
                  type="file"
                  accept="image/*"
                  multiple
                  class="hidden"
                  @change="onFilesChange"
                />
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
