<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

const { t } = useI18n()

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

const vk = computed(() => page.props.social?.find((p) => p.key === 'vkontakte') ?? null)
const enabled = computed(() => Boolean(vk.value?.configured))

const label = computed(() => {
  if (props.mode === 'link') {
    return t('ui.social.link_vk')
  }

  return props.register ? t('ui.social.register_vk') : t('ui.social.login_vk')
})

function buildStartUrl(): string {
  const base = props.mode === 'link' ? '/user/socials/link/vk' : '/auth/vk'

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
    error.value = t('ui.social.agreement_required')
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
      class="flex w-full cursor-pointer items-center justify-center gap-3 rounded-xl border border-border bg-white px-4 py-3 text-sm font-medium text-foreground shadow-sm transition-all duration-200 hover:border-[#0077FF]/40 hover:shadow-md active:scale-[0.98]"
      @click="start"
    >
      <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0077FF] text-xs font-bold text-white">
        VK
      </span>
      <span>{{ label }}</span>
    </button>
    <p v-if="error" class="field-error">{{ error }}</p>
  </div>
</template>
