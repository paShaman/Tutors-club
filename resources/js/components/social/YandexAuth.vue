<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'

const props = withDefaults(defineProps<{
  mode?: 'login' | 'link'
  register?: boolean
  agreementRequired?: boolean
  agreement?: boolean
}>(), {
  mode: 'login',
  register: false,
  agreementRequired: false,
  agreement: false,
})

const page = usePage<SharedProps>()

const error = ref('')

const yandex = computed(() => page.props.social?.find((p) => p.key === 'yandex') ?? null)
const enabled = computed(() => Boolean(yandex.value?.configured))

const label = computed(() => {
  if (props.mode === 'link') {
    return 'Привязать Яндекс ID'
  }

  return props.register ? 'Регистрация через Яндекс ID' : 'Войти через Яндекс ID'
})

function buildStartUrl(): string {
  const base = props.mode === 'link' ? '/user/socials/link/yandex' : '/auth/yandex'

  if (!props.register && !(props.agreementRequired && props.agreement === true)) {
    return base
  }

  const params = new URLSearchParams()

  if (props.register) {
    params.set('register', '1')
  }

  if (props.agreementRequired && props.agreement === true) {
    params.set('agreement', '1')
  }

  return `${base}?${params.toString()}`
}

function start(): void {
  if (props.agreementRequired && props.agreement !== true) {
    error.value = 'Необходимо согласие на обработку персональных данных'
    return
  }

  error.value = ''

  window.location.assign(buildStartUrl())
}
</script>

<template>
  <div v-if="enabled" class="w-full">
    <button
      type="button"
      class="flex w-full cursor-pointer items-center justify-center gap-3 rounded-xl border border-border bg-white px-4 py-3 text-sm font-medium text-foreground shadow-sm transition-all duration-200 hover:border-[#FC3F1D]/40 hover:shadow-md active:scale-[0.98]"
      @click="start"
    >
      <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#FC3F1D] text-xs font-bold text-white">
        Я
      </span>
      <span>{{ label }}</span>
    </button>
    <p v-if="error" class="mt-1 text-xs text-destructive">{{ error }}</p>
  </div>
</template>
