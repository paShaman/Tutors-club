<script setup lang="ts">
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'
import { useToast } from '@/lib/toast'
import ToastItem from './ToastItem.vue'
import { useI18n } from '@/lib/i18n'

const page = usePage<SharedProps>()
const toast = useToast()
const { t } = useI18n()

// Inertia-флеши бэкенда (lng('success.*') / lng('error.*')) показываем как тосты
watch(
  () => [page.props.flash?.success, page.props.flash?.error] as const,
  ([success, error]) => {
    if (success) {
      toast.success(success)
    }

    if (error) {
      toast.error(error)
    }
  },
  { immediate: true },
)
</script>

<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed inset-x-0 bottom-0 z-[100] flex flex-col items-center gap-2 p-4 sm:items-end"
      :aria-label="t('ui.toast.notifications')"
    >
      <TransitionGroup
        tag="div"
        class="flex w-full max-w-sm flex-col gap-2 sm:w-96"
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="translate-y-2 opacity-0 sm:translate-x-4 sm:translate-y-0"
        move-class="transition-transform duration-200"
      >
        <ToastItem v-for="item in toast.toasts" :key="item.id" :toast="item" />
      </TransitionGroup>
    </div>
  </Teleport>
</template>
