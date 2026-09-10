<script setup lang="ts">
import { Teleport, Transition } from 'vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

defineProps<{
  show: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  variant?: 'danger' | 'default'
}>()

const emit = defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

function onConfirm() {
  emit('confirm')
}

function onCancel() {
  emit('cancel')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="show"
        class="fixed inset-0 z-[80] overflow-y-auto bg-black/50"
      >
        <div class="flex min-h-full items-center justify-center p-4" @click.self="onCancel">
          <Transition name="modal">
            <div
              v-if="show"
              class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl z-[90]"
            >
              <h3 v-if="title" class="text-lg font-semibold text-foreground">
                {{ title }}
              </h3>
              <p v-if="message" class="mt-2 text-sm text-muted-foreground">
                {{ message }}
              </p>
              <div class="mt-6 flex justify-end gap-3">
                <Button variant="outline" @click="onCancel">
                  {{ cancelText ?? t('ui.common.cancel') }}
                </Button>
                <Button
                  :variant="variant === 'danger' ? 'destructive' : 'default'"
                  @click="onConfirm"
                >
                  {{ confirmText ?? t('ui.common.confirm') }}
                </Button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

.modal-enter-active {
  transition: all 0.2s ease;
}
.modal-leave-active {
  transition: all 0.15s ease;
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(8px);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(4px);
}
</style>