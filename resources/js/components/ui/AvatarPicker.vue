<script setup lang="ts">
import { ref, computed } from 'vue'
import { Camera, Loader2, Upload, Trash2 } from 'lucide-vue-next'
import { useHttp } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import ImageCropper from '@/components/ui/ImageCropper.vue'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  modelValue?: string | null
  name?: string
  circleClass?: string
  hint?: string
  showRemove?: boolean
}>(), {
  modelValue: null,
  name: '',
  circleClass: 'h-20 w-20 text-2xl',
  hint: '',
  showRemove: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const MAX_FILE_SIZE = 5 * 1024 * 1024

interface AvatarUploadResponse {
  success: boolean
  data: unknown
}

type AvatarForm = { avatar: File | null }

// Загрузка идёт через встроенный клиент Inertia (useHttp): multipart, CSRF и
// обработка 419 — на его стороне. Эндпоинт отвечает 200 и при ошибках валидации,
// поэтому неуспех разбираем в onSuccess.
const upload = useHttp<AvatarForm, AvatarUploadResponse>({ avatar: null })

const inputRef = ref<HTMLInputElement | null>(null)
const busy = computed(() => upload.processing)
const error = ref('')
const cropSrc = ref<string | null>(null)

function avatarUrlFrom(data: AvatarUploadResponse): string | null {
  const raw = data?.data
  if (raw && typeof raw === 'object' && typeof (raw as { avatar?: unknown }).avatar === 'string') {
    return (raw as { avatar: string }).avatar
  }
  return null
}

function uploadErrorMessage(data: AvatarUploadResponse): string {
  const raw = data?.data
  if (typeof raw === 'string') {
    return raw
  }
  if (raw && typeof raw === 'object') {
    const messages = Object.values(raw as Record<string, unknown>)
      .flatMap((value) => (Array.isArray(value) ? value : [value]))
      .filter((value): value is string => typeof value === 'string')

    if (messages.length) {
      return messages.join('\n')
    }
  }
  return t('ui.upload.failed')
}

const displaySrc = computed(() => (cropSrc.value ? null : props.modelValue) || null)

function openPicker() {
  if (busy.value) return
  error.value = ''
  inputRef.value?.click()
}

function removePhoto() {
  if (busy.value) return
  error.value = ''
  emit('update:modelValue', '')
}

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || busy.value) return

  if (!file.type.startsWith('image/')) {
    error.value = t('ui.avatar.not_image')
    return
  }

  // Show crop modal instead of uploading right away
  if (cropSrc.value) {
    URL.revokeObjectURL(cropSrc.value)
  }
  error.value = ''
  cropSrc.value = URL.createObjectURL(file)
}

function onCropCancel() {
  if (cropSrc.value) {
    URL.revokeObjectURL(cropSrc.value)
  }
  cropSrc.value = null
}

function onCropApply(blob: Blob) {
  if (cropSrc.value) {
    URL.revokeObjectURL(cropSrc.value)
  }
  cropSrc.value = null

  error.value = ''

  if (blob.size > MAX_FILE_SIZE) {
    error.value = t('ui.upload.too_large')
    return
  }

  upload.avatar = new File([blob], 'avatar.jpg', { type: blob.type || 'image/jpeg' })

  upload.post('/avatar/upload', {
    onSuccess: (data) => {
      const url = avatarUrlFrom(data)

      if (url) {
        emit('update:modelValue', url)
        return
      }

      error.value = uploadErrorMessage(data)
    },
    onHttpException: (response) => {
      error.value = response.status === 419 ? t('ui.upload.session_expired') : t('ui.upload.failed')
    },
    onNetworkError: () => {
      error.value = t('ui.upload.network')
    },
  })
}
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center gap-5">
      <button
        type="button"
        class="relative cursor-pointer rounded-full"
        :disabled="busy"
        :title="t('ui.avatar.upload')"
        @click="openPicker"
      >
        <UserAvatar
          :name="name"
          :src="displaySrc"
          :class="cn(
            'bg-gradient-to-br from-primary to-purple-500 font-semibold text-white shadow-md',
            circleClass,
          )"
        />
        <span
          class="absolute -bottom-0.5 -right-0.5 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-primary text-white shadow transition hover:bg-primary/90"
        >
          <Loader2 v-if="busy" class="h-3.5 w-3.5 animate-spin" />
          <Camera v-else class="h-3.5 w-3.5" />
        </span>
      </button>

      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition-all hover:border-foreground/20 hover:shadow-md disabled:opacity-50"
            :disabled="busy"
            @click="openPicker"
          >
            <Loader2 v-if="busy" class="h-3.5 w-3.5 animate-spin" />
            <Upload v-else class="h-3.5 w-3.5" />
            {{ busy ? t('ui.avatar.uploading') : t('ui.avatar.upload') }}
          </button>
          <button
            v-if="showRemove && displaySrc"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
            @click="removePhoto"
          >
            <Trash2 class="h-3.5 w-3.5" />
            {{ t('ui.avatar.remove') }}
          </button>
        </div>
        <p class="text-xs text-muted-foreground max-w-[260px]">
          {{ hint || t('ui.avatar.hint') }}
        </p>
        <p v-if="error" class="text-xs font-medium text-destructive whitespace-pre-line">
          {{ error }}
        </p>
      </div>
    </div>

    <input
      ref="inputRef"
      type="file"
      accept="image/jpeg,image/png,image/webp"
      class="hidden"
      @change="onFileChange"
    />

    <ImageCropper v-if="cropSrc" :src="cropSrc" @cancel="onCropCancel" @apply="onCropApply" />
  </div>
</template>
