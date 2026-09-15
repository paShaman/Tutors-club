<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import type { SelectOption } from '@/components/ui/Select.vue'
import { useI18n } from '@/lib/i18n'
import { useScrollLock } from '@/lib/scrollLock'

export type SubscriptionFormData = {
  user_id: number
  plan: string
  period: string
  expires_at: string
}

const props = defineProps<{
  show: boolean
  initial?: SubscriptionFormData | null
  plans: string[]
  periods: string[]
}>()

useScrollLock(() => props.show)

const emit = defineEmits<{
  close: []
  submit: [form: SubscriptionFormData]
}>()

const { t } = useI18n()

const emptyForm = (): SubscriptionFormData => ({
  user_id: 0,
  plan: props.plans[0] ?? 'free',
  period: props.periods[0] ?? 'm',
  expires_at: '',
})

const form = ref<SubscriptionFormData>(emptyForm())

watch(() => props.initial, (val) => {
  form.value = val ? { ...val } : emptyForm()
}, { immediate: true })

watch(() => props.show, (val) => {
  if (val && !props.initial) {
    form.value = emptyForm()
  }
})

const planOptions = computed<SelectOption<string>[]>(() =>
  props.plans.map((plan) => ({ value: plan, label: t(`ui.tariff.plans.${plan}`) })),
)

const periodOptions = computed<SelectOption<string>[]>(() =>
  props.periods.map((period) => ({ value: period, label: t(`ui.users.subscription.periods.${period}`) })),
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
            <h2 class="popup-title mb-5">{{ t('ui.users.subscription.title') }}</h2>

            <form @submit.prevent="emit('submit', form)" class="space-y-4">
              <div>
                <label class="field-label">{{ t('ui.users.subscription.plan') }}</label>
                <Select v-model="form.plan" :options="planOptions" :aria-label="t('ui.users.subscription.plan')" />
              </div>

              <div>
                <label class="field-label">{{ t('ui.users.subscription.period_label') }}</label>
                <Select v-model="form.period" :options="periodOptions" :aria-label="t('ui.users.subscription.period_label')" />
              </div>

              <div>
                <label class="field-label">{{ t('ui.users.subscription.expires_at') }}</label>
                <input v-model="form.expires_at" type="date" class="field w-full px-3.5 py-2.5" />
                <p class="mt-1 text-xs text-muted-foreground">{{ t('ui.users.subscription.expires_hint') }}</p>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <Button type="submit" class="flex-1">{{ t('ui.common.save') }}</Button>
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
