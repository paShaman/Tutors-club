<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { X, Landmark } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import type { CompanyRequisites, SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const page = usePage<SharedProps>()
const { t } = useI18n()

const requisites = computed<CompanyRequisites>(() => page.props.requisites ?? {})

const rows = computed(() => {
  const r = requisites.value

  return [
    { key: 'ogrnip', label: t('ui.requisites.fields.ogrnip'), value: r.ogrnip },
    { key: 'inn', label: t('ui.requisites.fields.inn'), value: r.inn },
    { key: 'address', label: t('ui.requisites.fields.address'), value: r.address },
    { key: 'account', label: t('ui.requisites.fields.account'), value: r.account },
    { key: 'corr_account', label: t('ui.requisites.fields.corr_account'), value: r.corr_account },
    { key: 'bik', label: t('ui.requisites.fields.bik'), value: r.bik },
  ].filter((row) => !!row.value)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
        @click.self="emit('close')"
      >
        <Transition name="modal" @after-leave="emit('close')">
          <div
            v-if="show"
            class="relative w-full max-w-md max-h-[80vh] flex flex-col rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-border/50">
              <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/10">
                  <Landmark class="h-4 w-4 text-primary" />
                </div>
                <h3 class="text-base font-semibold text-foreground">{{ t('ui.requisites.title') }}</h3>
              </div>
              <button
                class="rounded-lg p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors cursor-pointer"
                @click="emit('close')"
              >
                <X class="h-5 w-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-5 py-4">
              <p v-if="requisites.name" class="mb-4 text-sm font-semibold text-foreground">
                {{ requisites.name }}
              </p>

              <dl v-if="rows.length" class="space-y-3">
                <div v-for="row in rows" :key="row.key" class="space-y-0.5">
                  <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ row.label }}</dt>
                  <dd class="text-sm text-foreground/90 leading-snug">{{ row.value }}</dd>
                </div>
              </dl>

              <div v-if="requisites.email" class="mt-4 space-y-0.5">
                <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ t('ui.requisites.fields.email') }}</dt>
                <dd class="text-sm">
                  <a
                    :href="`mailto:${requisites.email}`"
                    class="font-medium text-primary hover:underline transition-colors"
                  >{{ requisites.email }}</a>
                </dd>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 border-t border-border/50 flex justify-end">
              <Button variant="outline" size="sm" @click="emit('close')">
                {{ t('ui.common.close') }}
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
