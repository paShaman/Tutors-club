<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import { useI18n } from '@/lib/i18n'
import { useScrollLock } from '@/lib/scrollLock'

export type PromoFormData = {
  banner_id: number | null
  title: string
  message: string
  button_text: string
  button_url: string
  starts_at: string
  ends_at: string
  dismiss_days: number
  is_active: boolean
}

const props = defineProps<{
  show: boolean
  initial?: PromoFormData | null
}>()

useScrollLock(() => props.show)

const emit = defineEmits<{
  close: []
  submit: [form: PromoFormData]
}>()

const { t } = useI18n()

const emptyForm = (): PromoFormData => ({
  banner_id: null,
  title: '',
  message: '',
  button_text: '',
  button_url: '',
  starts_at: '',
  ends_at: '',
  dismiss_days: 7,
  is_active: true,
})

const form = ref<PromoFormData>(emptyForm())

watch(() => props.initial, (val) => {
  form.value = val ? { ...val } : emptyForm()
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initial) {
    form.value = emptyForm()
  }
})

const title = computed(() =>
  props.initial?.banner_id ? t('ui.promo.form.edit_title') : t('ui.promo.form.add_title'),
)
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="show" class="popup-overlay z-60" @click="emit('close')" />
    </Transition>
    <Transition name="modal">
      <div v-if="show" class="popup-layer z-70">
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('close')">
          <Card class="popup-card max-w-lg">
            <h2 class="popup-title mb-5">{{ title }}</h2>

            <form @submit.prevent="emit('submit', form)" class="space-y-4">
              <div>
                <label class="field-label">{{ t('ui.promo.form.title') }}</label>
                <input
                  v-model="form.title"
                  maxlength="255"
                  class="field w-full px-3.5 py-2.5"
                  :placeholder="t('ui.promo.form.title_placeholder')"
                />
              </div>

              <div>
                <label class="field-label">{{ t('ui.promo.form.message') }}</label>
                <textarea
                  v-model="form.message"
                  required
                  rows="3"
                  maxlength="1000"
                  class="field w-full px-3.5 py-2.5 resize-none"
                  :placeholder="t('ui.promo.form.message_placeholder')"
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="field-label">{{ t('ui.promo.form.button_text') }}</label>
                  <input
                    v-model="form.button_text"
                    maxlength="100"
                    class="field w-full px-3.5 py-2.5"
                    :placeholder="t('ui.promo.form.button_text_placeholder')"
                  />
                </div>
                <div>
                  <label class="field-label">{{ t('ui.promo.form.button_url') }}</label>
                  <input
                    v-model="form.button_url"
                    maxlength="255"
                    class="field w-full px-3.5 py-2.5"
                    :placeholder="t('ui.promo.form.button_url_placeholder')"
                  />
                </div>
              </div>

              <div>
                <label class="field-label">{{ t('ui.promo.form.period') }}</label>
                <div class="grid grid-cols-2 gap-4">
                  <input
                    v-model="form.starts_at"
                    type="date"
                    class="field w-full px-3.5 py-2.5"
                    :aria-label="t('ui.promo.form.starts_at')"
                  />
                  <input
                    v-model="form.ends_at"
                    type="date"
                    class="field w-full px-3.5 py-2.5"
                    :aria-label="t('ui.promo.form.ends_at')"
                  />
                </div>
                <p class="mt-1 text-xs text-muted-foreground">{{ t('ui.promo.form.period_hint') }}</p>
              </div>

              <div>
                <label class="field-label">{{ t('ui.promo.form.dismiss_days') }}</label>
                <input
                  v-model.number="form.dismiss_days"
                  type="number"
                  min="1"
                  max="365"
                  required
                  class="field w-full px-3.5 py-2.5"
                />
                <p class="mt-1 text-xs text-muted-foreground">{{ t('ui.promo.form.dismiss_days_hint') }}</p>
              </div>

              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="rounded border-border text-primary focus:ring-primary/30"
                />
                <span class="text-sm text-foreground">{{ t('ui.promo.form.is_active') }}</span>
              </label>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1">
                  {{ form.banner_id ? t('ui.common.save') : t('ui.common.add') }}
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
