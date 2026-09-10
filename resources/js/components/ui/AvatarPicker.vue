<script setup lang="ts">
import { ref, computed } from 'vue'
import { Camera, Loader2, Upload, Trash2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { uploadAvatar } from '@/lib/upload'
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

const inputRef = ref<HTMLInputElement | null>(null)
const busy = ref(false)
const error = ref('')
const cropSrc = ref<string | null>(null)

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

async function onCropApply(blob: Blob) {
  if (cropSrc.value) {
    URL.revokeObjectURL(cropSrc.value)
  }
  cropSrc.value = null

  busy.value = true
  error.value = ''
  try {
    const url = await uploadAvatar(blob, 'avatar.jpg')
    emit('update:modelValue', url)
  } catch (err) {
    error.value = err instanceof Error ? err.message : t('ui.avatar.upload_error')
  } finally {
    busy.value = false
  }
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
