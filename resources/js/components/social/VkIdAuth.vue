<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import * as VKID from '@vkid/sdk'
import type { SharedProps } from '@/types'

interface VkLoginPayload {
  code: string
  device_id: string
}

const props = withDefaults(defineProps<{
  mode?: 'login' | 'link'
}>(), {
  mode: 'login',
})

const page = usePage<SharedProps>()

const container = ref<HTMLElement | null>(null)
const error = ref('')
const processing = ref(false)

const vkid = computed(() => page.props.vkid ?? null)
const enabled = computed(() => Boolean(vkid.value?.app && vkid.value?.redirectUrl))

let oneTap: VKID.OneTap | null = null
let destroyed = false

async function handleLoginSuccess(payload: VkLoginPayload): Promise<void> {
  if (processing.value || destroyed) {
    return
  }

  processing.value = true
  error.value = ''

  try {
    const tokens = await VKID.Auth.exchangeCode(payload.code, payload.device_id)

    router.post(props.mode === 'link' ? '/user/socials/link' : '/auth/vk', {
      access_token: tokens.access_token,
    }, {
      preserveScroll: true,
      onError: () => {
        error.value = props.mode === 'link' ? 'Не удалось привязать VK ID' : 'Не удалось войти через VK ID'
      },
      onFinish: () => {
        processing.value = false
      },
    })
  } catch (e) {
    processing.value = false
    error.value = props.mode === 'link' ? 'Не удалось привязать VK ID' : 'Не удалось войти через VK ID'
  }
}

function handleError(): void {
  error.value = 'Не удалось загрузить виджет VK ID'
}

onMounted(() => {
  if (!enabled.value || !container.value || destroyed) {
    return
  }

  VKID.Config.init({
    app: vkid.value!.app!,
    redirectUrl: vkid.value!.redirectUrl!,
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
