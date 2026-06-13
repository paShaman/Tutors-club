<script setup lang="ts">
import { Teleport, Transition } from 'vue'
import Button from '@/components/ui/Button.vue'
import { AlertTriangle, CheckCircle2, XCircle, Info } from 'lucide-vue-next'

defineProps<{
  show: boolean
  message: string
  variant?: 'success' | 'error' | 'warning' | 'info'
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function onClose() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="show"
        class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
        @click.self="onClose"
      >
        <Transition name="modal">
          <div
            v-if="show"
            class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl z-[90]"
          >
            <div class="flex flex-col items-center text-center">
              <div
                :class="{
                  'text-emerald-500': variant === 'success',
                  'text-red-500': variant === 'error',
                  'text-amber-500': variant === 'warning',
                  'text-blue-500': variant === 'info' || !variant,
                }"
                class="mb-4"
              >
                <CheckCircle2 v-if="variant === 'success'" class="h-10 w-10" />
                <XCircle v-else-if="variant === 'error'" class="h-10 w-10" />
                <AlertTriangle v-else-if="variant === 'warning'" class="h-10 w-10" />
                <Info v-else class="h-10 w-10" />
              </div>
              <p class="text-sm text-foreground whitespace-pre-wrap">
                {{ message }}
              </p>
            </div>
            <div class="mt-6 flex justify-center">
              <Button
                :variant="variant === 'error' || variant === 'warning' ? 'destructive' : 'primary'"
                @click="onClose"
              >
                OK
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