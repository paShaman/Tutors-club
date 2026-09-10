<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import * as VKID from '@vkid/sdk'
import type { SharedProps } from '@/types'
import { useI18n } from '@/lib/i18n'

interface VkLoginPayload {
  code: string
  device_id: string
}

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
const { t } = useI18n()

const container = ref<HTMLElement | null>(null)
const error = ref('')
const processing = ref(false)

const vk = computed(() => page.props.social?.find((p) => p.key === 'vkontakte') ?? null)
const enabled = computed(() => Boolean(vk.value?.configured && vk.value?.app && vk.value?.redirectUrl))

let oneTap: VKID.OneTap | null = null
let destroyed = false

async function handleLoginSuccess(payload: VkLoginPayload): Promise<void> {
  if (processing.value || destroyed) {
    return
  }

  if (props.agreementRequired && props.agreement !== true) {
    error.value = t('ui.social.agreement_required')
    return
  }

  processing.value = true
  error.value = ''

  try {
    const tokens = await VKID.Auth.exchangeCode(payload.code, payload.device_id)

    router.post(props.mode === 'link' ? '/user/socials/link' : '/auth/vk', {
      access_token: tokens.access_token,
      ...(props.register ? { register: true } : {}),
      ...(props.agreementRequired ? { agreement: props.agreement === true } : {}),
    }, {
      preserveScroll: true,
      onError: () => {
        error.value = props.mode === 'link' ? t('ui.social.vk_link_error') : t('ui.social.vk_login_error')
      },
      onFinish: () => {
        processing.value = false
      },
    })
  } catch (e) {
    processing.value = false
    error.value = props.mode === 'link' ? t('ui.social.vk_link_error') : t('ui.social.vk_login_error')
  }
}

function handleError(): void {
  error.value = t('ui.social.vk_widget_error')
}

onMounted(() => {
  if (!enabled.value || !container.value || destroyed) {
    return
  }

  VKID.Config.init({
    app: vk.value!.app!,
    redirectUrl: vk.value!.redirectUrl!,
    responseMode: VKID.ConfigResponseMode.Callback,
    source: VKID.ConfigSource.LOWCODE,
    scope: 'vkid.personal_info email',
  })

  oneTap = new VKID.OneTap()

  oneTap
    .on(VKID.WidgetEvents.ERROR, handleError)
    .on(VKID.OneTapInternalEvents.LOGIN_SUCCESS, handleLoginSuccess)
    .render({
      container: container.value,
      showAlternativeLogin: true,
    })
})

onBeforeUnmount(() => {
  destroyed = true

  if (oneTap) {
    oneTap
      .off(VKID.WidgetEvents.ERROR, handleError)
      .off(VKID.OneTapInternalEvents.LOGIN_SUCCESS, handleLoginSuccess)
    oneTap.close()
  }
})
</script>

<template>
  <div v-if="enabled" class="w-full">
    <div ref="container" class="w-full min-h-11"></div>
    <p v-if="error" class="mt-1 text-xs text-destructive">{{ error }}</p>
  </div>
</template>
