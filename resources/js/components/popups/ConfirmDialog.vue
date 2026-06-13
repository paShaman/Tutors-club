<script setup lang="ts">
import { Teleport, Transition } from 'vue'
import Button from '@/components/ui/Button.vue'

defineProps<{
  show: boolean
  title?: string
  message: string
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
        class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
        @click.self="onCancel"
      >
        <Transition name="modal">
          <div
            v-if="show"
            class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl z-[90]"
          >
            <h3 v-if="title" class="text-lg font-semibold text-foreground">
              {{ title }}
            </h3>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ message }}
            </p>
            <div class="mt-6 flex justify-end gap-3">
              <Button variant="outline" @click="onCancel">
                {{ cancelText ?? 'Отмена' }}
              </Button>
              <Button
                :variant="variant === 'danger' ? 'destructive' : 'primary'"
                @click="onConfirm"
              >
                {{ confirmText ?? 'Подтвердить' }}
              </Button>
            </div>
          </div>
        </Transition>
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